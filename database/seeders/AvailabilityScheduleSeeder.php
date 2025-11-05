<?php

namespace Database\Seeders;

use App\Models\AvailabilitySchedule;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class AvailabilityScheduleSeeder extends Seeder
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

        // Define days of week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        $daysOfWeek = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        // Time slots for each day: 9 AM - 11 AM and 1 PM - 4 PM
        $timeSlots = [
            ['start_time' => '09:00:00', 'end_time' => '11:00:00'],
            ['start_time' => '13:00:00', 'end_time' => '16:00:00'],
        ];

        $createdCount = 0;

        // Create availability schedules for all days
        foreach ($daysOfWeek as $dayOfWeek => $dayName) {
            foreach ($timeSlots as $slot) {
                AvailabilitySchedule::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => $user->id,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                    ],
                    [
                        'is_active' => true,
                    ]
                );
                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} availability schedule entries for all 7 days.");
        $this->command->info('Each day has 2 time slots: 9 AM - 11 AM and 1 PM - 4 PM');
    }
}

