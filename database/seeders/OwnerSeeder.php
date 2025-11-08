<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create owner user (tenant_id = null)
        User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'tenant_id' => null,
                'first_name' => 'System',
                'last_name' => 'Owner',
                'mobile' => '01000000000',
                'whatsapp' => '',
                'password' => Hash::make('password'),
                'timezone' => 'Africa/Cairo',
                'language' => 'en',
            ]
        );
    }
}
