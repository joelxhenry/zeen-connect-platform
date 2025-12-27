<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    feePayer: 'provider' | 'client';
    zeenFeeRate: number;
    gatewayFeeRate: number;
    exampleAmount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    exampleAmount: 10000,
});

const totalFeeRate = computed(() => props.zeenFeeRate + props.gatewayFeeRate);

const zeenFee = computed(() =>
    Math.round(props.exampleAmount * (props.zeenFeeRate / 100))
);

const gatewayFee = computed(() =>
    Math.round(props.exampleAmount * (props.gatewayFeeRate / 100))
);

const totalFees = computed(() => zeenFee.value + gatewayFee.value);

const clientPays = computed(() =>
    props.feePayer === 'client'
        ? props.exampleAmount + totalFees.value
        : props.exampleAmount
);

const providerReceives = computed(() =>
    props.feePayer === 'client'
        ? props.exampleAmount
        : props.exampleAmount - totalFees.value
);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-JM', {
        style: 'currency',
        currency: 'JMD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <p class="text-sm font-medium text-gray-700 mb-3">
            Example: {{ formatCurrency(exampleAmount) }} service
        </p>

        <div class="space-y-2 text-sm">
            <!-- Fee breakdown -->
            <div class="flex justify-between text-gray-600">
                <span>Zeen fee ({{ zeenFeeRate }}%)</span>
                <span>{{ formatCurrency(zeenFee) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Gateway fee ({{ gatewayFeeRate }}%)</span>
                <span>{{ formatCurrency(gatewayFee) }}</span>
            </div>
            <div class="flex justify-between text-gray-600 border-t border-gray-200 pt-2">
                <span>Total fees ({{ totalFeeRate }}%)</span>
                <span class="font-medium">{{ formatCurrency(totalFees) }}</span>
            </div>

            <!-- Results -->
            <div class="border-t border-gray-300 pt-3 mt-3 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-700">Client pays</span>
                    <span class="font-semibold text-gray-900">{{ formatCurrency(clientPays) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700">You receive</span>
                    <span class="font-semibold text-green-600">{{ formatCurrency(providerReceives) }}</span>
                </div>
            </div>
        </div>

        <!-- Info text -->
        <p class="text-xs text-gray-500 mt-3">
            <template v-if="feePayer === 'client'">
                Fees are added to the client's total as a "transaction fee".
            </template>
            <template v-else>
                Fees are deducted from your payout. Client sees only the service price.
            </template>
        </p>
    </div>
</template>
