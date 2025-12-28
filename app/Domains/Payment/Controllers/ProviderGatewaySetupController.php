<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Payment\Enums\GatewayProvider;
use App\Domains\Payment\Models\Gateway;
use App\Domains\Payment\Models\ProviderGatewayConfig;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProviderGatewaySetupController extends Controller
{
    /**
     * Show gateway selection or current configuration.
     */
    public function index(Request $request): Response
    {
        $provider = $request->user()->provider;

        // Get provider's configured gateways
        $configuredGateways = ProviderGatewayConfig::forProvider($provider->id)
            ->with('gateway')
            ->get()
            ->map(fn ($config) => $this->formatGatewayConfig($config));

        // Get available gateways that the provider hasn't configured
        // Only WiPay is supported - filter to ensure only WiPay is returned
        $configuredSlugs = $configuredGateways->pluck('slug')->toArray();
        $availableGateways = Gateway::active()
            ->where('slug', 'wipay') // Only WiPay is supported
            ->whereNotIn('slug', $configuredSlugs)
            ->get()
            ->map(fn ($gateway) => $this->formatAvailableGateway($gateway));

        // Get provider's banking info
        $bankingInfo = [
            'bank_name' => $provider->bank_name,
            'bank_account_number' => $provider->bank_account_number ? '****'.substr($provider->bank_account_number, -4) : null,
            'bank_account_holder_name' => $provider->bank_account_holder_name,
            'bank_branch_code' => $provider->bank_branch_code,
            'bank_account_type' => $provider->bank_account_type,
            'is_verified' => $provider->banking_info_verified,
            'has_banking_info' => $provider->hasBankingInfo(),
        ];

        return Inertia::render('Provider/Payments/Setup/Index', [
            'hasGatewayConfigured' => $configuredGateways->isNotEmpty(),
            'configuredGateways' => $configuredGateways,
            'availableGateways' => $availableGateways,
            'bankingInfo' => $bankingInfo,
        ]);
    }

    /**
     * Show gateway-specific configuration form.
     */
    public function create(Request $request, string $gateway): Response
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (! $gatewayModel || ! $gatewayModel->is_active) {
            abort(404, 'Gateway not available');
        }

        // Check if already configured
        $existingConfig = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->first();

        if ($existingConfig) {
            return redirect()->route('provider.payments.setup.edit', $gateway);
        }

        return Inertia::render('Provider/Payments/Setup/WiPay', [
            'gateway' => $this->formatAvailableGateway($gatewayModel),
            'config' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * Show edit form for existing gateway configuration.
     */
    public function edit(Request $request, string $gateway): Response
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (! $gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->firstOrFail();

        return Inertia::render('Provider/Payments/Setup/WiPay', [
            'gateway' => $this->formatAvailableGateway($gatewayModel),
            'config' => $this->formatGatewayConfig($config),
            'isEdit' => true,
        ]);
    }

    /**
     * Store new gateway credentials.
     */
    public function store(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (! $gatewayModel || ! $gatewayModel->is_active) {
            abort(404, 'Gateway not available');
        }

        // Check if already configured
        $existingConfig = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->exists();

        if ($existingConfig) {
            return back()->withErrors(['gateway' => 'This gateway is already configured']);
        }

        $validated = $this->validateCredentials($request);

        // Determine if this should be primary (first gateway for provider)
        $hasExistingGateway = ProviderGatewayConfig::forProvider($provider->id)->exists();

        $config = ProviderGatewayConfig::create([
            'provider_id' => $provider->id,
            'gateway_id' => $gatewayModel->id,
            'credentials' => $validated['credentials'],
            'merchant_account_id' => $validated['merchant_account_id'] ?? null,
            'is_active' => false, // Active after verification
            'is_primary' => ! $hasExistingGateway,
            'verification_status' => 'pending',
        ]);

        return redirect()
            ->route('provider.payments.setup.index')
            ->with('success', 'WiPay credentials saved. Please verify your account to start receiving payments.');
    }

    /**
     * Update existing gateway credentials.
     */
    public function update(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (! $gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->firstOrFail();

        $validated = $this->validateCredentials($request);

        $config->update([
            'credentials' => $validated['credentials'],
            'merchant_account_id' => $validated['merchant_account_id'] ?? $config->merchant_account_id,
            'verification_status' => 'pending', // Re-verify on credential change
            'is_active' => false,
            'verified_at' => null,
        ]);

        return redirect()
            ->route('provider.payments.setup.index')
            ->with('success', 'WiPay credentials updated. Please verify your account again.');
    }

    /**
     * Delete gateway configuration.
     */
    public function destroy(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (! $gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->firstOrFail();

        $wasPrimary = $config->is_primary;
        $config->delete();

        // If deleted was primary, make another one primary
        if ($wasPrimary) {
            $nextConfig = ProviderGatewayConfig::forProvider($provider->id)
                ->verified()
                ->first();

            $nextConfig?->makePrimary();
        }

        return redirect()
            ->route('provider.payments.setup.index')
            ->with('success', 'WiPay configuration removed.');
    }

    /**
     * Verify gateway credentials.
     */
    public function verify(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (! $gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->firstOrFail();

        // TODO: Implement actual gateway-specific verification
        // For now, we'll simulate verification
        // In production, this would call the gateway API to verify credentials
        $verified = $this->verifyGatewayCredentials($config);

        if ($verified) {
            $config->markAsVerified();

            return redirect()
                ->route('provider.payments.setup.index')
                ->with('success', 'WiPay account verified successfully!');
        }

        $config->markAsFailed();

        return redirect()
            ->route('provider.payments.setup.index')
            ->withErrors(['verification' => 'Failed to verify credentials. Please check your account details and try again.']);
    }

    /**
     * Set gateway as primary.
     */
    public function makePrimary(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (! $gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->verified()
            ->firstOrFail();

        $config->makePrimary();

        return redirect()
            ->route('provider.payments.setup.index')
            ->with('success', 'WiPay is now your primary payment gateway.');
    }

    /**
     * Format gateway config for frontend.
     */
    private function formatGatewayConfig(ProviderGatewayConfig $config): array
    {
        $gateway = $config->gateway;

        return [
            'id' => $config->id,
            'slug' => $gateway->slug,
            'name' => $gateway->name,
            'icon' => 'pi pi-credit-card',
            'is_verified' => $config->isVerified(),
            'is_pending' => $config->isPending(),
            'is_failed' => $config->isFailed(),
            'is_primary' => $config->is_primary,
            'is_active' => $config->is_active,
            'verification_status' => $config->verification_status,
            'verification_status_label' => $this->getVerificationStatusLabel($config->verification_status),
            'supports_split' => $gateway->supports_split,
            'supports_escrow' => $gateway->supports_escrow,
            'merchant_account_id' => $config->merchant_account_id,
            'created_at' => $config->created_at->format('M j, Y'),
            'verified_at' => $config->verified_at?->format('M j, Y'),
        ];
    }

    /**
     * Format available gateway for frontend.
     */
    private function formatAvailableGateway(Gateway $gateway): array
    {
        return [
            'slug' => $gateway->slug,
            'name' => $gateway->name,
            'icon' => 'pi pi-credit-card',
            'description' => 'Caribbean payment gateway with split payment support. Receive funds directly to your WiPay account.',
            'supports_split' => $gateway->supports_split,
            'supports_escrow' => $gateway->supports_escrow,
            'features' => $this->getGatewayFeatures($gateway),
        ];
    }

    /**
     * Validate WiPay credentials.
     */
    private function validateCredentials(Request $request): array
    {
        $rules = [
            'account_number' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
        ];

        $validated = $request->validate($rules);

        return [
            'credentials' => [
                'account_number' => $validated['account_number'],
                'api_key' => $validated['api_key'],
                'environment' => $validated['environment'],
            ],
            'merchant_account_id' => $validated['account_number'],
        ];
    }

    /**
     * Verify gateway credentials with the provider.
     */
    private function verifyGatewayCredentials(ProviderGatewayConfig $config): bool
    {
        // TODO: Implement actual verification with WiPay API
        // This would call WiPay's API to verify the account credentials

        // For now, simulate successful verification for development
        return true;
    }

    /**
     * Get gateway features list.
     */
    private function getGatewayFeatures(Gateway $gateway): array
    {
        $features = [];

        if ($gateway->supports_split) {
            $features[] = [
                'icon' => 'pi pi-bolt',
                'label' => 'Direct Split Payments',
                'description' => 'Receive your portion instantly when customer pays',
            ];
        }

        if ($gateway->supports_escrow) {
            $features[] = [
                'icon' => 'pi pi-calendar',
                'label' => 'Scheduled Payouts',
                'description' => 'Receive earnings on your preferred schedule',
            ];
        }

        $features[] = [
            'icon' => 'pi pi-shield',
            'label' => 'Secure Processing',
            'description' => 'Bank-level encryption for all transactions',
        ];

        return $features;
    }

    /**
     * Get verification status label.
     */
    private function getVerificationStatusLabel(string $status): string
    {
        return match ($status) {
            'verified' => 'Verified',
            'pending' => 'Pending Verification',
            'failed' => 'Verification Failed',
            default => 'Unknown',
        };
    }
}
