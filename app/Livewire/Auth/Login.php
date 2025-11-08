<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        session()->regenerate();

        $user = Auth::user();
        
        // Redirect owners to owner panel, tenants to admin panel
        if ($user->isOwner()) {
            return redirect()->intended('/owner');
        }

        // For tenant users, redirect to their tenant's admin panel
        // Filament will handle tenant routing automatically
        return redirect()->intended('/admin');
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.landing-page');
    }
}

