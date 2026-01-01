<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Actions\CreateTeamMemberDirectlyAction;
use App\Domains\Provider\Actions\InviteTeamMemberAction;
use App\Domains\Provider\Actions\UpdateTeamMemberPermissionsAction;
use App\Domains\Provider\Enums\TeamPermission;
use App\Domains\Provider\Mail\TeamInvitationMail;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Provider\Requests\CreateTeamMemberDirectRequest;
use App\Domains\Provider\Requests\InviteTeamMemberRequest;
use App\Domains\Provider\Requests\UpdateTeamMemberRequest;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TeamMemberController extends Controller
{
    public function __construct(
        private InviteTeamMemberAction $inviteAction,
        private CreateTeamMemberDirectlyAction $createDirectAction,
        private UpdateTeamMemberPermissionsAction $updatePermissionsAction,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Display list of team members.
     */
    public function index(): Response
    {
        $provider = Auth::user()->provider;

        // Check if tier supports team
        if (! $provider->supportsTeam()) {
            return Inertia::render('Provider/Team/Upgrade', [
                'currentTier' => $provider->getTier()->value,
                'currentTierLabel' => $provider->getTier()->label(),
            ]);
        }

        $teamMembers = $provider->teamMembers()
            ->with('user:id,name,email,avatar')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TeamMember $member) => [
                'id' => $member->id,
                'uuid' => $member->uuid,
                'email' => $member->email,
                'name' => $member->display_name,
                'title' => $member->title,
                'avatar' => $member->avatar,
                'permissions' => $member->permissions,
                'permissions_summary' => $member->permissions_summary,
                'status' => $member->status->value,
                'status_label' => $member->status->label(),
                'status_color' => $member->status->color(),
                'invited_at' => $member->invited_at?->format('M d, Y'),
                'accepted_at' => $member->accepted_at?->format('M d, Y'),
                'is_expired' => $member->isInvitationExpired(),
            ]);

        $teamInfo = $this->subscriptionService->calculateTeamMemberCharges($provider);

        return Inertia::render('Provider/Team/Index', [
            'teamMembers' => $teamMembers,
            'teamInfo' => $teamInfo,
            'permissions' => TeamPermission::all(),
            'permissionsGrouped' => TeamPermission::grouped(),
        ]);
    }

    /**
     * Show form to invite/add team member.
     */
    public function create(): Response
    {
        $provider = Auth::user()->provider;

        if (! $provider->supportsTeam()) {
            return redirect()->route('provider.team.index');
        }

        $teamInfo = $this->subscriptionService->calculateTeamMemberCharges($provider);

        return Inertia::render('Provider/Team/Create', [
            'permissions' => TeamPermission::all(),
            'permissionsGrouped' => TeamPermission::grouped(),
            'presets' => TeamPermission::presets(),
            'defaultPermissions' => TeamPermission::defaults(),
            'teamInfo' => $teamInfo,
        ]);
    }

    /**
     * Store a new team member (invite or direct create).
     */
    public function store(InviteTeamMemberRequest|CreateTeamMemberDirectRequest $request): RedirectResponse
    {
        $provider = Auth::user()->provider;
        $data = $request->validated();

        // Determine which action to use based on the request type
        if ($request instanceof CreateTeamMemberDirectRequest || isset($data['name']) && ! empty($data['name'])) {
            // Direct creation
            $result = $this->createDirectAction->execute($provider, $data);
            $message = 'Team member added successfully.';

            if ($result['temporary_password']) {
                $message .= ' Temporary password: '.$result['temporary_password'];
            }
        } else {
            // Email invitation
            $this->inviteAction->execute($provider, $data);
            $message = 'Invitation sent successfully.';
        }

        return redirect()
            ->route('provider.team.index')
            ->with('success', $message);
    }

    /**
     * Show form to edit team member permissions.
     */
    public function edit(TeamMember $member): Response
    {
        $provider = Auth::user()->provider;

        // Verify the team member belongs to this provider
        if ($member->provider_id !== $provider->id) {
            abort(403);
        }

        return Inertia::render('Provider/Team/Edit', [
            'member' => [
                'id' => $member->id,
                'uuid' => $member->uuid,
                'email' => $member->email,
                'name' => $member->display_name,
                'title' => $member->title,
                'avatar' => $member->avatar,
                'permissions' => $member->permissions,
                'status' => $member->status->value,
                'status_label' => $member->status->label(),
                'invited_at' => $member->invited_at?->format('M d, Y H:i'),
                'accepted_at' => $member->accepted_at?->format('M d, Y H:i'),
                'is_pending' => $member->isPending(),
                'is_expired' => $member->isInvitationExpired(),
            ],
            'permissions' => TeamPermission::all(),
            'permissionsGrouped' => TeamPermission::grouped(),
            'presets' => TeamPermission::presets(),
        ]);
    }

    /**
     * Update team member permissions.
     */
    public function update(UpdateTeamMemberRequest $request, TeamMember $member): RedirectResponse
    {
        $this->updatePermissionsAction->execute($member, $request->validated());

        return redirect()
            ->route('provider.team.index')
            ->with('success', 'Team member permissions updated successfully.');
    }

    /**
     * Remove a team member.
     */
    public function destroy(TeamMember $member): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Verify the team member belongs to this provider
        if ($member->provider_id !== $provider->id) {
            abort(403);
        }

        $member->delete();

        return redirect()
            ->route('provider.team.index')
            ->with('success', 'Team member removed successfully.');
    }

    /**
     * Resend invitation email.
     */
    public function resendInvite(TeamMember $member): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Verify the team member belongs to this provider
        if ($member->provider_id !== $provider->id) {
            abort(403);
        }

        // Only resend for pending invitations
        if (! $member->isPending()) {
            return back()->with('error', 'Cannot resend invitation for active or suspended members.');
        }

        // Generate new token and send email
        $member->generateInvitationToken();
        Mail::to($member->email)->queue(new TeamInvitationMail($member));

        return back()->with('success', 'Invitation resent successfully.');
    }

    /**
     * Suspend a team member.
     */
    public function suspend(TeamMember $member): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Verify the team member belongs to this provider
        if ($member->provider_id !== $provider->id) {
            abort(403);
        }

        // Can't suspend pending members
        if ($member->isPending()) {
            return back()->with('error', 'Cannot suspend pending invitations. Delete them instead.');
        }

        $member->suspend();

        return back()->with('success', 'Team member suspended successfully.');
    }

    /**
     * Reactivate a suspended team member.
     */
    public function reactivate(TeamMember $member): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Verify the team member belongs to this provider
        if ($member->provider_id !== $provider->id) {
            abort(403);
        }

        // Can only reactivate suspended members
        if (! $member->isSuspended()) {
            return back()->with('error', 'Only suspended members can be reactivated.');
        }

        $member->reactivate();

        return back()->with('success', 'Team member reactivated successfully.');
    }
}
