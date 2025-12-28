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
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'source' => 'founding-members',
            'is_founding_member' => true,
        ]);

        return back()->with('success', true);
    }
}
