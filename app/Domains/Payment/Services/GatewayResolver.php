<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\Enums\GatewayProvider;
use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Gateways\DirectSplit\WiPayDirectSplitGateway;
use App\Domains\Payment\Gateways\Escrow\WiPayEscrowGateway;
use App\Domains\Payment\Models\ProviderGatewayConfig;
use App\Domains\Provider\Models\Provider;
use InvalidArgumentException;

class GatewayResolver
{
    public function __construct(
        protected LedgerService $ledgerService
    ) {}

    /**
     * Resolve the appropriate gateway for a provider.
     */
    public function resolve(Provider $provider): PaymentGatewayInterface
    {
        // First, check for active provider gateway config (for split payments)
        $providerConfig = $provider->gatewayConfigs()
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->with('gateway')
            ->orderByDesc('is_primary')
            ->first();

        if ($providerConfig && $providerConfig->gateway->supports_split) {
            // Provider has linked account - use split gateway
            return $this->instantiateSplitGateway(
                $providerConfig->getDecryptedCredentials()
            );
        }

        // Default to escrow with WiPay
        return $this->instantiateEscrowGateway();
    }

    /**
     * Resolve gateway by provider name (for webhooks).
     */
    public function resolveByName(string $gatewayName): PaymentGatewayInterface
    {
        $provider = GatewayProvider::tryFrom($gatewayName);

        if (! $provider) {
            throw new InvalidArgumentException("Unknown gateway: {$gatewayName}");
        }

        return $this->instantiateEscrowGateway();
    }

    /**
     * Determine the gateway type for a provider.
     */
    public function determineGatewayType(Provider $provider): GatewayType
    {
        $hasLinkedAccount = $provider->gatewayConfigs()
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->whereHas('gateway', fn ($q) => $q->where('supports_split', true))
            ->exists();

        return $hasLinkedAccount ? GatewayType::SPLIT : GatewayType::ESCROW;
    }

    /**
     * Get the provider's active gateway config.
     */
    public function getProviderConfig(Provider $provider): ?ProviderGatewayConfig
    {
        return $provider->gatewayConfigs()
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->orderByDesc('is_primary')
            ->first();
    }

    /**
     * Instantiate a split gateway with provider credentials.
     */
    protected function instantiateSplitGateway(array $credentials): PaymentGatewayInterface
    {
        return new WiPayDirectSplitGateway($credentials);
    }

    /**
     * Instantiate an escrow gateway.
     */
    protected function instantiateEscrowGateway(): PaymentGatewayInterface
    {
        return new WiPayEscrowGateway($this->ledgerService);
    }

    /**
     * Get the platform's default gateway provider.
     */
    protected function getDefaultProvider(): GatewayProvider
    {
        return GatewayProvider::WIPAY;
    }

    /**
     * Get all available gateways.
     *
     * @return array<GatewayProvider>
     */
    public function getAvailableGateways(): array
    {
        return GatewayProvider::cases();
    }

    /**
     * Get gateways that support split payments.
     *
     * @return array<GatewayProvider>
     */
    public function getSplitCapableGateways(): array
    {
        return array_filter(
            GatewayProvider::cases(),
            fn (GatewayProvider $p) => $p->supportsSplit()
        );
    }
}
