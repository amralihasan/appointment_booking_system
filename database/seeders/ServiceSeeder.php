<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the demo tenant and user
        $tenant = Tenant::where('slug', 'demo-coach')->first();
        
        if (!$tenant) {
            $this->command->warn('Demo tenant not found. Please run DemoSeeder first.');
            return;
        }

        $user = User::where('tenant_id', $tenant->id)
            ->where('email', 'demo@example.com')
            ->first();

        if (!$user) {
            $this->command->warn('Demo user not found. Please run DemoSeeder first.');
            return;
        }

        // One-to-one services (type: 'one')
        $oneToOneServices = [
            [
                'name' => 'Private Tennis Lesson',
                'description' => 'One-on-one personalized tennis training session. Perfect for beginners and advanced players looking to improve their technique, strategy, and overall game performance.',
                'price' => 150.00,
                'duration' => 60, // 1 hour in minutes
                'type' => 'one',
                'slug' => 'private-tennis-lesson',
            ],
            [
                'name' => 'Premium Private Training',
                'description' => 'Extended private training session with intensive coaching. Focus on advanced techniques, match play strategies, and physical conditioning.',
                'price' => 200.00,
                'duration' => 90, // 1.5 hours in minutes
                'type' => 'one',
                'slug' => 'premium-private-training',
            ],
        ];

        // Group services (type: 'group')
        $groupServices = [
            [
                'name' => 'Group Tennis Class',
                'description' => 'Join a small group of tennis enthusiasts for a fun and competitive training session. Learn from peers while improving your skills in a supportive environment.',
                'price' => 75.00,
                'duration' => 60, // 1 hour in minutes
                'type' => 'group',
                'max_spots' => 4,
                'slug' => 'group-tennis-class',
            ],
            [
                'name' => 'Weekend Tennis Clinic',
                'description' => 'Comprehensive weekend tennis clinic for all skill levels. Includes technique drills, match play, and fitness training. Great for players who want intensive weekend training.',
                'price' => 120.00,
                'duration' => 120, // 2 hours in minutes
                'type' => 'group',
                'max_spots' => 8,
                'slug' => 'weekend-tennis-clinic',
            ],
        ];

        $createdCount = 0;

        // Create one-to-one services
        foreach ($oneToOneServices as $serviceData) {
            Service::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'slug' => $serviceData['slug'],
                ],
                [
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'type' => $serviceData['type'],
                    'max_spots' => null, // One-to-one doesn't need max_spots
                    'is_active' => true,
                ]
            );
            $createdCount++;
        }

        // Create group services
        foreach ($groupServices as $serviceData) {
            Service::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'slug' => $serviceData['slug'],
                ],
                [
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'type' => $serviceData['type'],
                    'max_spots' => $serviceData['max_spots'],
                    'is_active' => true,
                ]
            );
            $createdCount++;
        }

        $this->command->info("Created {$createdCount} services for Tennis coach.");
        $this->command->info('- 2 one-to-one services: Private Tennis Lesson, Premium Private Training');
        $this->command->info('- 2 group services: Group Tennis Class (4 spots), Weekend Tennis Clinic (8 spots)');
    }
}

