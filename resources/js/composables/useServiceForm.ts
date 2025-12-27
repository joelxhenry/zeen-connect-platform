import { computed } from 'vue';
import type {
    TierRestrictions,
    BookingSettings,
    DepositTypeOption,
    DurationOption,
    CancellationPolicyOption,
    ValidationResult,
} from '@/types/service';

export function useServiceForm(tierRestrictions: TierRestrictions) {
    /**
     * Deposit type options filtered by tier capabilities.
     */
    const depositTypeOptions = computed((): DepositTypeOption[] => {
        const options: DepositTypeOption[] = [];

        // Only enterprise can disable deposits
        if (tierRestrictions.can_disable_deposit) {
            options.push({
                value: 'none',
                label: 'No deposit required',
            });
        } else {
            options.push({
                value: 'none',
                label: 'No deposit required',
                disabled: true,
            });
        }

        options.push({
            value: 'fixed',
            label: 'Fixed amount (JMD)',
        });

        options.push({
            value: 'percentage',
            label: 'Percentage of total',
        });

        return options;
    });

    /**
     * Available deposit type values (excluding disabled options).
     */
    const availableDepositTypes = computed(() => {
        return depositTypeOptions.value
            .filter(opt => !opt.disabled)
            .map(opt => opt.value);
    });

    /**
     * Minimum deposit percentage based on tier.
     */
    const minDepositPercentage = computed(() => tierRestrictions.minimum_deposit_percentage);

    /**
     * Minimum service price based on tier.
     */
    const minServicePrice = computed(() => tierRestrictions.minimum_service_price);

    /**
     * Duration options for service.
     */
    const durationOptions = computed((): DurationOption[] => [
        { value: 15, label: '15 minutes' },
        { value: 30, label: '30 minutes' },
        { value: 45, label: '45 minutes' },
        { value: 60, label: '1 hour' },
        { value: 90, label: '1.5 hours' },
        { value: 120, label: '2 hours' },
        { value: 150, label: '2.5 hours' },
        { value: 180, label: '3 hours' },
        { value: 240, label: '4 hours' },
        { value: 300, label: '5 hours' },
        { value: 360, label: '6 hours' },
        { value: 420, label: '7 hours' },
        { value: 480, label: '8 hours' },
    ]);

    /**
     * Cancellation policy options.
     */
    const cancellationPolicyOptions = computed((): CancellationPolicyOption[] => [
        {
            value: 'flexible',
            label: 'Flexible',
            description: 'Full refund up to 24 hours before',
        },
        {
            value: 'moderate',
            label: 'Moderate',
            description: 'Full refund up to 5 days before, 50% after',
        },
        {
            value: 'strict',
            label: 'Strict',
            description: '50% refund up to 7 days before, none after',
        },
    ]);

    /**
     * Validate service price against tier minimum.
     */
    const validatePrice = (price: number): ValidationResult => {
        if (minServicePrice.value > 0 && price < minServicePrice.value) {
            return {
                valid: false,
                message: `Your ${tierRestrictions.tier_label} tier requires a minimum service price of ${tierRestrictions.minimum_service_price_display}.`,
            };
        }
        return { valid: true, message: null };
    };

    /**
     * Validate deposit type against tier capabilities.
     */
    const validateDepositType = (depositType: string): ValidationResult => {
        if (depositType === 'none' && !tierRestrictions.can_disable_deposit) {
            return {
                valid: false,
                message: `Your ${tierRestrictions.tier_label} tier requires a deposit to cover platform fees.`,
            };
        }
        return { valid: true, message: null };
    };

    /**
     * Validate deposit amount against tier minimum.
     */
    const validateDepositAmount = (
        depositType: string,
        depositAmount: number | null
    ): ValidationResult => {
        if (depositType === 'percentage') {
            const amount = depositAmount || 0;
            if (amount < minDepositPercentage.value) {
                return {
                    valid: false,
                    message: `Minimum deposit percentage is ${minDepositPercentage.value}% to cover the ${tierRestrictions.platform_fee_rate}% platform fee.`,
                };
            }
        }
        return { valid: true, message: null };
    };

    /**
     * Get the default deposit type for the tier.
     */
    const getDefaultDepositType = (): 'none' | 'fixed' | 'percentage' => {
        if (tierRestrictions.can_disable_deposit) {
            return 'none';
        }
        return 'percentage';
    };

    /**
     * Get the default deposit amount for the tier.
     */
    const getDefaultDepositAmount = (): number => {
        return minDepositPercentage.value;
    };

    /**
     * Check if the tier can customize deposits.
     */
    const canCustomizeDeposit = computed(() => tierRestrictions.can_customize_deposit);

    /**
     * Check if the tier can disable deposits.
     */
    const canDisableDeposit = computed(() => tierRestrictions.can_disable_deposit);

    return {
        // Options
        depositTypeOptions,
        availableDepositTypes,
        durationOptions,
        cancellationPolicyOptions,

        // Tier values
        minDepositPercentage,
        minServicePrice,
        canCustomizeDeposit,
        canDisableDeposit,

        // Validation
        validatePrice,
        validateDepositType,
        validateDepositAmount,

        // Defaults
        getDefaultDepositType,
        getDefaultDepositAmount,

        // Help text
        depositHelpText: tierRestrictions.deposit_help_text,
        priceHelpText: tierRestrictions.price_help_text,
    };
}
