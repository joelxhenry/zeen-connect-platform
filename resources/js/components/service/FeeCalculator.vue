<script setup lang="ts">
import { computed } from 'vue';
import Tag from 'primevue/tag';
import type { TierRestrictions } from '@/types/service';

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
    tierRestrictions?: TierRestrictions;
    depositType?: 'none' | 'fixed' | 'percentage';
    depositAmount?: number | null;
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-JM', {
        style: 'currency',
        currency: 'JMD',
        minimumFractionDigits: 2,
    }).format(amount);
};

/**
 * Deposit validation based on tier restrictions.
 */
const depositValidation = computed(() => {
    if (!props.tierRestrictions) {
        return { valid: true, message: null, coversCommission: true };
    }

    const restrictions = props.tierRestrictions;
    const depositType = props.depositType || 'percentage';
    const depositAmount = props.depositAmount ?? props.feeRates.deposit_percentage;

    // Check if deposit is disabled when not allowed
    if (depositType === 'none' && !restrictions.can_disable_deposit) {
        return {
            valid: false,
            message: `Your ${restrictions.tier_label} tier requires a deposit to cover the ${restrictions.platform_fee_rate}% platform fee.`,
            coversCommission: false,
        };
    }

    // For percentage deposits, check minimum
    if (depositType === 'percentage') {
        const percentage = depositAmount || 0;
        if (percentage < restrictions.minimum_deposit_percentage) {
            return {
                valid: false,
                message: `Deposit must be at least ${restrictions.minimum_deposit_percentage}% to cover the ${restrictions.platform_fee_rate}% platform fee.`,
                coversCommission: false,
            };
        }
    }

    // For fixed deposits, check if it covers the platform fee
    if (depositType === 'fixed' && props.price > 0) {
        const fixedAmount = depositAmount || 0;
        const platformFee = props.price * (restrictions.platform_fee_rate / 100);
        if (fixedAmount < platformFee) {
            return {
                valid: false,
                message: `Deposit of ${formatCurrency(fixedAmount)} does not cover the ${formatCurrency(platformFee)} platform fee.`,
                coversCommission: false,
            };
        }
    }

    return { valid: true, message: null, coversCommission: true };
});

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

        <!-- Deposit Validation Warning -->
        <div v-if="!depositValidation.valid" class="fee-calculator__warning">
            <i class="pi pi-exclamation-triangle"></i>
            <span>{{ depositValidation.message }}</span>
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

.fee-calculator__warning {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: #fef3c7;
    border-top: 1px solid #fcd34d;
    font-size: 0.875rem;
    color: #92400e;
}

.fee-calculator__warning i {
    margin-top: 0.125rem;
    flex-shrink: 0;
}
</style>
