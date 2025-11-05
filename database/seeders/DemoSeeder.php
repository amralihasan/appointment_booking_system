<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Coach',
            'slug' => 'demo-coach',
            'subdomain' => 'demo-coach',
            'status' => 'active',
        ]);

        // Create a demo user (coach)
        User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Demo',
            'last_name' => 'Coach',
            'mobile' => '01234567890',
            'email' => 'demo@example.com',
            'whatsapp' => '01234567890',
            'password' => Hash::make('password'),
            'timezone' => 'Africa/Cairo',
            'language' => 'en',
        ]);

        $this->command->info('Demo tenant and user created successfully!');
        $this->command->info('Email: demo@example.com');
        $this->command->info('Password: password');
    }
}
