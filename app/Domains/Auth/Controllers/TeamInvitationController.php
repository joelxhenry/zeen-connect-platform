<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Provider\Actions\AcceptTeamInvitationAction;
use App\Domains\Provider\Models\TeamMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController extends Controller
{
    public function __construct(
        private AcceptTeamInvitationAction $acceptAction
    ) {}

    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token): Response|RedirectResponse
    {
        $teamMember = TeamMember::byToken($token)
            ->with('provider:id,business_name,slug')
            ->first();

        if (! $teamMember) {
            return redirect()->route('login')->with('error', 'Invalid or expired invitation link.');
        }

        if (! $teamMember->isPending()) {
            return redirect()->route('login')->with('error', 'This invitation has already been processed.');
        }

        if ($teamMember->isInvitationExpired()) {
            return redirect()->route('login')->with('error', 'This invitation has expired. Please ask for a new invitation.');
        }

        // Check if user is already logged in
        $currentUser = Auth::user();

        return Inertia::render('Auth/AcceptTeamInvitation', [
            'invitation' => [
                'token' => $token,
                'email' => $teamMember->email,
                'name' => $teamMember->name,
                'provider_name' => $teamMember->provider->business_name,
            ],
            'isLoggedIn' => (bool) $currentUser,
            'currentUser' => $currentUser ? [
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ] : null,
            'emailMatches' => $currentUser && $currentUser->email === $teamMember->email,
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $teamMember = TeamMember::byToken($token)->first();

        if (! $teamMember) {
            return redirect()->route('login')->with('error', 'Invalid or expired invitation link.');
        }

        if (! $teamMember->isPending()) {
            return redirect()->route('login')->with('error', 'This invitation has already been processed.');
        }

        if ($teamMember->isInvitationExpired()) {
            return redirect()->route('login')->with('error', 'This invitation has expired.');
        }

        $currentUser = Auth::user();

        // If user is logged in and email matches, accept directly
        if ($currentUser && $currentUser->email === $teamMember->email) {
            try {
                $this->acceptAction->execute($teamMember, $currentUser);

                return redirect()
                    ->route('provider.dashboard')
                    ->with('success', "You've joined {$teamMember->provider->business_name}'s team!");
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        // If user is logged in but email doesn't match, they need to logout
        if ($currentUser && $currentUser->email !== $teamMember->email) {
            return back()->with('error', 'Please log out and use an account with the email: '.$teamMember->email);
        }

        // User is not logged in, validate the registration data
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $result = $this->acceptAction->execute($teamMember, null, [
                'name' => $request->name,
                'password' => $request->password,
            ]);

            // Log the user in
            Auth::login($result['user']);

            return redirect()
                ->route('provider.dashboard')
                ->with('success', "Welcome! You've joined {$teamMember->provider->business_name}'s team.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
