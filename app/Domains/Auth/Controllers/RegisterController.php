<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\RegisterProviderAction;
use App\Domains\Auth\Actions\RegisterUserAction;
use App\Domains\Auth\Requests\RegisterProviderRequest;
use App\Domains\Auth\Requests\RegisterRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        private RegisterUserAction $registerUserAction,
        private RegisterProviderAction $registerProviderAction,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registerUserAction->execute($request->validated());

        Auth::login($user);

        return redirect()->route('client.dashboard');
    }

    public function showProvider(): Response
    {
        return Inertia::render('Auth/RegisterProvider');
    }

    public function storeProvider(RegisterProviderRequest $request): RedirectResponse
    {
        $user = $this->registerProviderAction->execute($request->validated());

        Auth::login($user);

        return redirect()->route('provider.dashboard');
    }
}
