<?php

namespace App\Livewire\Auth;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class Register extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $mobile = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $tenant_name = '';
    public string $tenant_slug = '';
    public ?int $category_id = null;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'mobile' => 'required|string|max:255',
        'password' => 'required|string|min:8|confirmed',
        'tenant_name' => 'required|string|max:255',
        'tenant_slug' => 'required|string|max:255|unique:tenants,slug',
        'category_id' => 'required|exists:categories,id',
    ];

    public function updatedTenantName($value)
    {
        $this->tenant_slug = Str::slug($value);
        $this->validateOnly('tenant_slug');
    }

    public function register()
    {
        $this->validate();

        // Create tenant
        $tenant = Tenant::create([
            'name' => $this->tenant_name,
            'slug' => $this->tenant_slug,
            'category_id' => $this->category_id,
        ]);

        // Create user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'whatsapp' => '', // Optional field, can be updated later in profile
            'password' => Hash::make($this->password),
            'timezone' => 'Africa/Cairo',
            'language' => app()->getLocale(),
        ]);

        Auth::login($user);

        session()->regenerate();

        return redirect()->intended('/admin');
    }

    public function render()
    {
        $categories = Category::active()->ordered()->get();
        
        return view('livewire.auth.register', [
            'categories' => $categories,
        ])
            ->layout('layouts.landing-page');
    }
}

