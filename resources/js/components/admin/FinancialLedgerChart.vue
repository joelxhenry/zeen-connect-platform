<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import Chart from 'primevue/chart';
import Select from 'primevue/select';

interface DataPoint {
    date: string;
    revenue: number;
    payouts: number;
}

const props = defineProps<{
    data: DataPoint[];
}>();

const timeRange = ref('30d');
const timeRangeOptions = [
    { label: 'Last 7 days', value: '7d' },
    { label: 'Last 30 days', value: '30d' },
    { label: 'Last 90 days', value: '90d' },
];

const chartData = computed(() => {
    const labels = props.data.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    return {
        labels,
        datasets: [
            {
                label: 'Revenue',
                data: props.data.map(d => d.revenue),
                borderColor: '#106B4F',
                backgroundColor: 'rgba(16, 107, 79, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#106B4F',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            },
            {
                label: 'Payouts',
                data: props.data.map(d => d.payouts),
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#F59E0B',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top' as const,
            align: 'end' as const,
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20,
                font: {
                    size: 12,
                },
            },
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
            backgroundColor: '#0F172A',
            titleFont: {
                size: 12,
            },
            bodyFont: {
                size: 12,
            },
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: function(context: any) {
                    const label = context.dataset.label || '';
                    const value = context.parsed.y || 0;
                    return `${label}: $${value.toLocaleString()}`;
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                font: {
                    size: 11,
                },
                color: '#94A3B8',
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: '#F1F5F9',
            },
            ticks: {
                font: {
                    size: 11,
                },
                color: '#94A3B8',
                callback: function(value: number) {
                    return '$' + value.toLocaleString();
                },
            },
        },
    },
    interaction: {
        mode: 'nearest' as const,
        axis: 'x' as const,
        intersect: false,
    },
};

const totalRevenue = computed(() => {
    return props.data.reduce((sum, d) => sum + d.revenue, 0);
});

const totalPayouts = computed(() => {
    return props.data.reduce((sum, d) => sum + d.payouts, 0);
});

const netProfit = computed(() => {
    return totalRevenue.value - totalPayouts.value;
});
</script>

<template>
    <div class="financial-chart">
        <div class="chart-header">
            <div class="chart-title">
                <h3>Financial Overview</h3>
                <p class="chart-subtitle">Revenue vs Payouts</p>
            </div>
            <Select
                v-model="timeRange"
                :options="timeRangeOptions"
                optionLabel="label"
                optionValue="value"
                class="time-range-select"
            />
        </div>

        <div class="chart-summary">
            <div class="summary-item">
                <span class="summary-label">Total Revenue</span>
                <span class="summary-value revenue">${{ totalRevenue.toLocaleString() }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Payouts</span>
                <span class="summary-value payouts">${{ totalPayouts.toLocaleString() }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Net Profit</span>
                <span class="summary-value profit" :class="{ negative: netProfit < 0 }">
                    ${{ netProfit.toLocaleString() }}
                </span>
            </div>
        </div>

        <div class="chart-container">
            <Chart type="line" :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>

<style scoped>
.financial-chart {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.chart-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.chart-title h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0F172A;
}

.chart-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: #64748B;
}

.time-range-select {
    width: 140px;
}

.chart-summary {
    display: flex;
    gap: 1.5rem;
    padding: 1rem;
    background-color: #F8FAFC;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.summary-label {
    font-size: 0.75rem;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0F172A;
}

.summary-value.revenue {
    color: #106B4F;
}

.summary-value.payouts {
    color: #F59E0B;
}

.summary-value.profit {
    color: #16A34A;
}

.summary-value.profit.negative {
    color: #DC2626;
}

.chart-container {
    flex: 1;
    min-height: 280px;
}

@media (max-width: 768px) {
    .chart-summary {
        flex-direction: column;
        gap: 0.75rem;
    }
}
</style>
