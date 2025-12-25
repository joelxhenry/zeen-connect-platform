<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWaitlistRequest;
use App\Models\WaitlistSubscriber;
use Inertia\Inertia;

class WaitlistController extends Controller
{
    public function show()
    {
        return Inertia::render('FoundingMembers');
    }

    public function store(StoreWaitlistRequest $request)
    {
        WaitlistSubscriber::create([
            'email' => $request->email,
            'name' => $request->name,
            'source' => 'founding-members',
            'is_founding_member' => true,
        ]);

        return back()->with('success', true);
    }
}
