<?php

namespace App\Domains\Provider\Requests;

use App\Domains\Provider\Enums\TeamPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InviteTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        // User must be authenticated and be a provider
        if (! Auth::check() || ! Auth::user()->isProvider()) {
            return false;
        }

        // Provider must support team members
        $provider = Auth::user()->provider;

        return $provider && $provider->supportsTeam();
    }

    public function rules(): array
    {
        $provider = Auth::user()->provider;

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                // Email must not already be a team member of this provider
                Rule::unique('team_members', 'email')
                    ->where('provider_id', $provider->id),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(TeamPermission::keys())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already a team member.',
            'permissions.required' => 'Please select at least one permission.',
            'permissions.min' => 'Please select at least one permission.',
            'permissions.*.in' => 'Invalid permission selected.',
        ];
    }
}
