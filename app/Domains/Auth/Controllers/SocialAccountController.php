<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\UnlinkSocialAccountAction;
use App\Domains\Auth\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class SocialAccountController extends Controller
{
    public function __construct(
        private UnlinkSocialAccountAction $unlinkAction
    ) {}

    /**
     * Initiate linking a social account.
     */
    public function link(Request $request, string $provider): Response
    {
        if (! SocialProvider::isValid($provider)) {
            abort(404, 'Invalid social provider');
        }

        // Store context for the callback
        session()->put('social_auth_context', 'link');
        session()->put('social_auth_intended', $request->header('Referer', route('client.profile.edit')));

        $driver = Socialite::driver($provider);

        if ($provider === 'apple') {
            $driver->scopes(['name', 'email']);
        }

        return $driver->redirect();
    }

    /**
     * Unlink a social account from the current user.
     */
    public function unlink(Request $request, string $provider): RedirectResponse
    {
        if (! SocialProvider::isValid($provider)) {
            abort(404, 'Invalid social provider');
        }

        try {
            $this->unlinkAction->execute($request->user(), $provider);

            return back()->with('success', ucfirst($provider) . ' account unlinked successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
