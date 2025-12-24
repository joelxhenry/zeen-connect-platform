<script setup lang="ts">
import { computed } from 'vue';
import Tag from 'primevue/tag';

interface FeeRates {
    tier: string;
    tier_label: string;
    deposit_percentage: number;
    platform_fee_rate: number;
    processing_fee_rate?: number;
    processing_fee_flat?: number;
    processing_fee_payer?: 'client' | 'provider';
}

const props = defineProps<{
    price: number;
    feeRates: FeeRates;
}>();

const calculations = computed(() => {
    const price = props.price || 0;
    const depositAmount = price * (props.feeRates.deposit_percentage / 100);
    const platformFee = price * (props.feeRates.platform_fee_rate / 100);

    // Bank/Transaction processing fee applies to all tiers
    const processingFeeRate = props.feeRates.processing_fee_rate || 2.9;
    const processingFeeFlat = props.feeRates.processing_fee_flat || 50;
    const processingFee = price * (processingFeeRate / 100) + processingFeeFlat;

    // For Free/Premium, platform absorbs the processing fee (it's included in their platform fee)
    // For Enterprise, provider or client pays it directly
    const isEnterprise = props.feeRates.tier === 'enterprise';
    const providerPaysProcessingFee = isEnterprise && props.feeRates.processing_fee_payer === 'provider';

    // Provider payout calculation
    let providerPayout = price - platformFee;
    if (providerPaysProcessingFee) {
        providerPayout -= processingFee;
    }

    return {
        depositAmount,
        platformFee,
        processingFee,
        processingFeeRate,
        processingFeeFlat,
        providerPayout,
        isEnterprise,
        providerPaysProcessingFee,
    };
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-JM', {
        style: 'currency',
        currency: 'JMD',
        minimumFractionDigits: 2,
    }).format(amount);
};

const tierSeverity = computed(() => {
    switch (props.feeRates.tier) {
        case 'enterprise':
            return 'success';
        case 'premium':
            return 'info';
        default:
            return 'secondary';
    }
});

const tierDescription = computed(() => {
    const tier = props.feeRates.tier;
    const bankFeeText = `${calculations.value.processingFeeRate}% + $${calculations.value.processingFeeFlat} bank fee`;
    if (tier === 'free') {
        return `${props.feeRates.platform_fee_rate}% platform fee + ${bankFeeText}, ${props.feeRates.deposit_percentage}% deposit required`;
    } else if (tier === 'premium') {
        return `${props.feeRates.platform_fee_rate}% platform fee + ${bankFeeText}, ${props.feeRates.deposit_percentage}% minimum deposit`;
    } else {
        return `No platform fee, ${bankFeeText} only`;
    }
});
</script>

<template>
    <div class="fee-calculator">
        <div class="fee-calculator__header">
            <div class="fee-calculator__title">
                <i class="pi pi-calculator"></i>
                <span>Fee Preview</span>
            </div>
            <Tag :value="feeRates.tier_label" :severity="tierSeverity" />
        </div>

        <div class="fee-calculator__content">
            <!-- Service Price -->
            <div class="fee-calculator__row">
                <span class="fee-calculator__label">Service Price</span>
                <span class="fee-calculator__value">{{ formatCurrency(price) }}</span>
            </div>

            <div class="fee-calculator__divider"></div>

            <!-- Deposit (if applicable) -->
            <div v-if="feeRates.deposit_percentage > 0" class="fee-calculator__row fee-calculator__row--deduction">
                <span class="fee-calculator__label">
                    Deposit ({{ feeRates.deposit_percentage }}%)
                    <span class="fee-calculator__note">client pays upfront</span>
                </span>
                <span class="fee-calculator__value">{{ formatCurrency(calculations.depositAmount) }}</span>
            </div>

            <!-- Platform Fee -->
            <div v-if="feeRates.platform_fee_rate > 0" class="fee-calculator__row fee-calculator__row--deduction">
                <span class="fee-calculator__label">
                    Platform Fee ({{ feeRates.platform_fee_rate }}%)
                </span>
                <span class="fee-calculator__value">-{{ formatCurrency(calculations.platformFee) }}</span>
            </div>

            <!-- Bank/Transaction Fee -->
            <div class="fee-calculator__row fee-calculator__row--deduction">
                <span class="fee-calculator__label">
                    Bank Fee ({{ calculations.processingFeeRate }}% + ${{ calculations.processingFeeFlat }})
                    <span class="fee-calculator__note">
                        <template v-if="calculations.isEnterprise">
                            {{ calculations.providerPaysProcessingFee ? 'you pay' : 'client pays' }}
                        </template>
                        <template v-else>
                            included in platform fee
                        </template>
                    </span>
                </span>
                <span class="fee-calculator__value">
                    <template v-if="calculations.isEnterprise">
                        {{ calculations.providerPaysProcessingFee ? '-' : '' }}{{ formatCurrency(calculations.processingFee) }}
                    </template>
                    <template v-else>
                        {{ formatCurrency(calculations.processingFee) }}
                    </template>
                </span>
            </div>

            <div class="fee-calculator__divider"></div>

            <!-- Provider Payout -->
            <div class="fee-calculator__row fee-calculator__row--total">
                <span class="fee-calculator__label">Your Payout</span>
                <span class="fee-calculator__value fee-calculator__value--highlight">
                    {{ formatCurrency(calculations.providerPayout) }}
                </span>
            </div>
        </div>

        <div class="fee-calculator__footer">
            <i class="pi pi-info-circle"></i>
            <span>{{ tierDescription }}</span>
        </div>
    </div>
</template>

<style scoped>
.fee-calculator {
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 0.75rem;
    overflow: hidden;
}

.fee-calculator__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background-color: #dcfce7;
    border-bottom: 1px solid #bbf7d0;
}

.fee-calculator__title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #166534;
}

.fee-calculator__content {
    padding: 1rem;
}

.fee-calculator__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.fee-calculator__row--deduction {
    color: #6b7280;
}

.fee-calculator__row--total {
    padding-top: 0.75rem;
}

.fee-calculator__label {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.fee-calculator__note {
    font-size: 0.75rem;
    color: #9ca3af;
}

.fee-calculator__value {
    font-weight: 500;
    font-variant-numeric: tabular-nums;
}

.fee-calculator__value--highlight {
    font-size: 1.125rem;
    font-weight: 700;
    color: #166534;
}

.fee-calculator__divider {
    height: 1px;
    background-color: #bbf7d0;
    margin: 0.5rem 0;
}

.fee-calculator__footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: #dcfce7;
    border-top: 1px solid #bbf7d0;
    font-size: 0.875rem;
    color: #166534;
}
</style>
