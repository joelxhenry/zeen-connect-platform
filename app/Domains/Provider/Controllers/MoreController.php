<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Subscription\Enums\SubscriptionFeature;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MoreController extends Controller
{
    /**
     * Display the settings hub page.
     */
    public function __invoke(): Response
    {
        $provider = Auth::user()->provider;

        return Inertia::render('Provider/More/Index', [
            'canManageTeam' => $provider->hasFeature(SubscriptionFeature::TEAM_MEMBERS),
        ]);
    }
}
