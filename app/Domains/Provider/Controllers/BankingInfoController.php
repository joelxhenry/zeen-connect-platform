<?php

namespace App\Domains\Provider\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BankingInfoController extends Controller
{
    /**
     * Common Jamaican banks for the dropdown.
     */
    private const JAMAICAN_BANKS = [
        'NCB' => 'National Commercial Bank',
        'SCOTIA' => 'Scotiabank Jamaica',
        'CIBC' => 'CIBC FirstCaribbean',
        'JMMB' => 'JMMB Bank',
        'SAGICOR' => 'Sagicor Bank',
        'VMBS' => 'Victoria Mutual Building Society',
        'JN' => 'JN Bank',
        'MAYBERRY' => 'Mayberry Investments',
    ];

    /**
     * Show the banking info form.
     */
    public function edit(Request $request): Response
    {
        $provider = $request->user()->provider;

        return Inertia::render('Provider/Payments/BankingInfo', [
            'bankingInfo' => [
                'bank_name' => $provider->bank_name,
                'bank_account_number' => $provider->bank_account_number,
                'bank_account_holder_name' => $provider->bank_account_holder_name,
                'bank_branch_code' => $provider->bank_branch_code,
                'bank_account_type' => $provider->bank_account_type,
                'is_verified' => $provider->banking_info_verified,
                'verified_at' => $provider->banking_info_verified_at?->format('M j, Y'),
            ],
            'hasWiPayAccount' => $provider->hasLinkedGateway(),
            'banks' => self::JAMAICAN_BANKS,
        ]);
    }

    /**
     * Update the provider's banking info.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder_name' => 'required|string|max:255',
            'bank_branch_code' => 'nullable|string|max:50',
            'bank_account_type' => ['required', Rule::in(['savings', 'checking'])],
        ]);

        $provider = $request->user()->provider;

        // Check if banking info changed (needs re-verification)
        $infoChanged = $provider->bank_account_number !== $validated['bank_account_number']
            || $provider->bank_name !== $validated['bank_name'];

        $provider->update([
            ...$validated,
            // Reset verification if critical info changed
            'banking_info_verified' => $infoChanged ? false : $provider->banking_info_verified,
            'banking_info_verified_at' => $infoChanged ? null : $provider->banking_info_verified_at,
        ]);

        $message = $infoChanged
            ? 'Banking information updated. Verification pending.'
            : 'Banking information updated successfully.';

        return back()->with('success', $message);
    }

    /**
     * Remove the provider's banking info.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        // Check if provider has WiPay account before removing banking info
        if (! $provider->hasLinkedGateway()) {
            return back()->withErrors([
                'banking' => 'You must have either a WiPay account or banking information configured to receive payments.',
            ]);
        }

        $provider->update([
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_holder_name' => null,
            'bank_branch_code' => null,
            'bank_account_type' => null,
            'banking_info_verified' => false,
            'banking_info_verified_at' => null,
        ]);

        return back()->with('success', 'Banking information removed.');
    }
}
