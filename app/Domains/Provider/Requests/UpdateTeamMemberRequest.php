<?php

namespace App\Domains\Provider\Requests;

use App\Domains\Provider\Enums\TeamPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        // User must be authenticated and be a provider
        if (! Auth::check() || ! Auth::user()->isProvider()) {
            return false;
        }

        $provider = Auth::user()->provider;
        $member = $this->route('member');

        // Verify the team member belongs to this provider
        return $member && $member->provider_id === $provider->id;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(TeamPermission::keys())],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'Please select at least one permission.',
            'permissions.min' => 'Please select at least one permission.',
            'permissions.*.in' => 'Invalid permission selected.',
        ];
    }
}
