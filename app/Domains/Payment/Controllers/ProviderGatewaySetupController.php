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
        $configuredSlugs = $configuredGateways->pluck('slug')->toArray();
        $availableGateways = Gateway::active()
            ->whereNotIn('slug', $configuredSlugs)
            ->get()
            ->map(fn ($gateway) => $this->formatAvailableGateway($gateway));

        return Inertia::render('Provider/Payments/Setup/Index', [
            'hasGatewayConfigured' => $configuredGateways->isNotEmpty(),
            'configuredGateways' => $configuredGateways,
            'availableGateways' => $availableGateways,
        ]);
    }

    /**
     * Show gateway-specific configuration form.
     */
    public function create(Request $request, string $gateway): Response
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (!$gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (!$gatewayModel || !$gatewayModel->is_active) {
            abort(404, 'Gateway not available');
        }

        // Check if already configured
        $existingConfig = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->first();

        if ($existingConfig) {
            return redirect()->route('provider.payments.setup.edit', $gateway);
        }

        return Inertia::render("Provider/Payments/Setup/{$this->getGatewayViewName($gatewayProvider)}", [
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

        if (!$gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (!$gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->firstOrFail();

        return Inertia::render("Provider/Payments/Setup/{$this->getGatewayViewName($gatewayProvider)}", [
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

        if (!$gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (!$gatewayModel || !$gatewayModel->is_active) {
            abort(404, 'Gateway not available');
        }

        // Check if already configured
        $existingConfig = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->exists();

        if ($existingConfig) {
            return back()->withErrors(['gateway' => 'This gateway is already configured']);
        }

        $validated = $this->validateCredentials($request, $gatewayProvider);

        // Determine if this should be primary (first gateway for provider)
        $hasExistingGateway = ProviderGatewayConfig::forProvider($provider->id)->exists();

        $config = ProviderGatewayConfig::create([
            'provider_id' => $provider->id,
            'gateway_id' => $gatewayModel->id,
            'credentials' => $validated['credentials'],
            'merchant_account_id' => $validated['merchant_account_id'] ?? null,
            'is_active' => false, // Active after verification
            'is_primary' => !$hasExistingGateway,
            'verification_status' => 'pending',
        ]);

        return redirect()
            ->route('provider.payments.setup.index')
            ->with('success', "{$gatewayProvider->label()} credentials saved. Please verify your account to start receiving payments.");
    }

    /**
     * Update existing gateway credentials.
     */
    public function update(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (!$gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (!$gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->firstOrFail();

        $validated = $this->validateCredentials($request, $gatewayProvider);

        $config->update([
            'credentials' => $validated['credentials'],
            'merchant_account_id' => $validated['merchant_account_id'] ?? $config->merchant_account_id,
            'verification_status' => 'pending', // Re-verify on credential change
            'is_active' => false,
            'verified_at' => null,
        ]);

        return redirect()
            ->route('provider.payments.setup.index')
            ->with('success', "{$gatewayProvider->label()} credentials updated. Please verify your account again.");
    }

    /**
     * Delete gateway configuration.
     */
    public function destroy(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (!$gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (!$gatewayModel) {
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
            ->with('success', "{$gatewayProvider->label()} configuration removed.");
    }

    /**
     * Verify gateway credentials.
     */
    public function verify(Request $request, string $gateway): RedirectResponse
    {
        $provider = $request->user()->provider;
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (!$gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (!$gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->firstOrFail();

        // TODO: Implement actual gateway-specific verification
        // For now, we'll simulate verification
        // In production, this would call the gateway API to verify credentials
        $verified = $this->verifyGatewayCredentials($config, $gatewayProvider);

        if ($verified) {
            $config->markAsVerified();

            return redirect()
                ->route('provider.payments.setup.index')
                ->with('success', "{$gatewayProvider->label()} account verified successfully!");
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

        if (!$gatewayProvider) {
            abort(404, 'Gateway not found');
        }

        $gatewayModel = Gateway::findBySlug($gateway);
        if (!$gatewayModel) {
            abort(404, 'Gateway not found');
        }

        $config = ProviderGatewayConfig::forProvider($provider->id)
            ->where('gateway_id', $gatewayModel->id)
            ->verified()
            ->firstOrFail();

        $config->makePrimary();

        return redirect()
            ->route('provider.payments.setup.index')
            ->with('success', "{$gatewayProvider->label()} is now your primary payment gateway.");
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
            'icon' => $this->getGatewayIcon($gateway->slug),
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
            'icon' => $this->getGatewayIcon($gateway->slug),
            'description' => $this->getGatewayDescription($gateway->slug),
            'supports_split' => $gateway->supports_split,
            'supports_escrow' => $gateway->supports_escrow,
            'features' => $this->getGatewayFeatures($gateway),
        ];
    }

    /**
     * Validate credentials based on gateway type.
     */
    private function validateCredentials(Request $request, GatewayProvider $gateway): array
    {
        $rules = match ($gateway) {
            GatewayProvider::WIPAY => [
                'account_number' => 'required|string|max:255',
                'api_key' => 'required|string|max:255',
                'environment' => ['required', Rule::in(['sandbox', 'production'])],
            ],
            GatewayProvider::FYGARO => [
                'merchant_id' => 'required|string|max:255',
                'api_key' => 'required|string|max:255',
                'secret_key' => 'required|string|max:255',
                'environment' => ['required', Rule::in(['sandbox', 'production'])],
            ],
            GatewayProvider::POWERTRANZ => [
                'merchant_id' => 'required|string|max:255',
                'password' => 'required|string|max:255',
                'terminal_id' => 'required|string|max:255',
                'environment' => ['required', Rule::in(['sandbox', 'production'])],
            ],
        };

        $validated = $request->validate($rules);

        // Structure credentials for storage
        $credentials = match ($gateway) {
            GatewayProvider::WIPAY => [
                'account_number' => $validated['account_number'],
                'api_key' => $validated['api_key'],
                'environment' => $validated['environment'],
            ],
            GatewayProvider::FYGARO => [
                'merchant_id' => $validated['merchant_id'],
                'api_key' => $validated['api_key'],
                'secret_key' => $validated['secret_key'],
                'environment' => $validated['environment'],
            ],
            GatewayProvider::POWERTRANZ => [
                'merchant_id' => $validated['merchant_id'],
                'password' => $validated['password'],
                'terminal_id' => $validated['terminal_id'],
                'environment' => $validated['environment'],
            ],
        };

        return [
            'credentials' => $credentials,
            'merchant_account_id' => $validated['merchant_id'] ?? $validated['account_number'] ?? null,
        ];
    }

    /**
     * Verify gateway credentials with the provider.
     */
    private function verifyGatewayCredentials(ProviderGatewayConfig $config, GatewayProvider $gateway): bool
    {
        // TODO: Implement actual verification with each gateway
        // This would call the respective gateway's API to verify credentials

        // For now, simulate successful verification for development
        return true;
    }

    /**
     * Get the Vue component name for a gateway.
     */
    private function getGatewayViewName(GatewayProvider $gateway): string
    {
        return match ($gateway) {
            GatewayProvider::WIPAY => 'WiPay',
            GatewayProvider::FYGARO => 'Fygaro',
            GatewayProvider::POWERTRANZ => 'PowerTranz',
        };
    }

    /**
     * Get gateway icon.
     */
    private function getGatewayIcon(string $slug): string
    {
        return match ($slug) {
            'wipay' => 'pi pi-credit-card',
            'fygaro' => 'pi pi-wallet',
            'powertranz' => 'pi pi-money-bill',
            default => 'pi pi-credit-card',
        };
    }

    /**
     * Get gateway description.
     */
    private function getGatewayDescription(string $slug): string
    {
        return match ($slug) {
            'wipay' => 'Caribbean payment gateway with split payment support. Receive funds directly to your WiPay account.',
            'fygaro' => 'Modern payment platform with flexible split payment options. Supports multiple Caribbean currencies.',
            'powertranz' => 'Enterprise payment processing. Funds are collected by the platform and paid out on schedule.',
            default => 'Payment gateway',
        };
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
