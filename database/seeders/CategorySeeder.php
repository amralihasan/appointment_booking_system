<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Fitness & Gym',
                'slug' => 'fitness-gym',
                'description' => 'Gym, Fitness Center, Personal Training, Yoga Studio, Pilates',
                'icon' => 'heroicon-o-bolt',
                'color' => '#ef4444',
                'sort_order' => 1,
            ],
            [
                'name' => 'Beauty & Salon',
                'slug' => 'beauty-salon',
                'description' => 'Hair Salon, Nail Salon, Barber Shop, Beauty Salon, Makeup Studio',
                'icon' => 'heroicon-o-star',
                'color' => '#ec4899',
                'sort_order' => 2,
            ],
            [
                'name' => 'Spa & Wellness',
                'slug' => 'spa-wellness',
                'description' => 'Spa, Massage Therapy, Wellness Center, Aromatherapy',
                'icon' => 'heroicon-o-heart',
                'color' => '#f59e0b',
                'sort_order' => 3,
            ],
            [
                'name' => 'Medical & Clinic',
                'slug' => 'medical-clinic',
                'description' => 'Medical Clinic, Dental Clinic, Veterinary Clinic, Physiotherapy, Chiropractic',
                'icon' => 'heroicon-o-shield-check',
                'color' => '#10b981',
                'sort_order' => 4,
            ],
            [
                'name' => 'Education & Tutoring',
                'slug' => 'education-tutoring',
                'description' => 'Tutoring Center, Language School, Music School, Driving School',
                'icon' => 'heroicon-o-academic-cap',
                'color' => '#3b82f6',
                'sort_order' => 5,
            ],
            [
                'name' => 'Legal & Consulting',
                'slug' => 'legal-consulting',
                'description' => 'Law Firm, Business Consulting, Financial Consulting, Tax Services',
                'icon' => 'heroicon-o-briefcase',
                'color' => '#6366f1',
                'sort_order' => 6,
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car Repair, Car Wash, Auto Detailing, Tire Service',
                'icon' => 'heroicon-o-truck',
                'color' => '#8b5cf6',
                'sort_order' => 7,
            ],
            [
                'name' => 'Pet Services',
                'slug' => 'pet-services',
                'description' => 'Pet Grooming, Pet Training, Pet Boarding, Pet Sitting',
                'icon' => 'heroicon-o-heart',
                'color' => '#f97316',
                'sort_order' => 8,
            ],
            [
                'name' => 'Photography',
                'slug' => 'photography',
                'description' => 'Photography Studio, Event Photography, Portrait Studio',
                'icon' => 'heroicon-o-camera',
                'color' => '#06b6d4',
                'sort_order' => 9,
            ],
            [
                'name' => 'Event Planning',
                'slug' => 'event-planning',
                'description' => 'Event Planner, Wedding Planner, Party Planning',
                'icon' => 'heroicon-o-calendar-days',
                'color' => '#ec4899',
                'sort_order' => 10,
            ],
            [
                'name' => 'Coaching',
                'slug' => 'coaching',
                'description' => 'Life Coaching, Business Coaching, Career Coaching',
                'icon' => 'heroicon-o-user-group',
                'color' => '#14b8a6',
                'sort_order' => 11,
            ],
            [
                'name' => 'Therapy & Counseling',
                'slug' => 'therapy-counseling',
                'description' => 'Psychotherapy, Counseling, Mental Health Services',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'color' => '#6366f1',
                'sort_order' => 12,
            ],
            [
                'name' => 'Restaurant & Cafe',
                'slug' => 'restaurant-cafe',
                'description' => 'Restaurant, Cafe, Catering Service',
                'icon' => 'heroicon-o-building-storefront',
                'color' => '#f59e0b',
                'sort_order' => 13,
            ],
            [
                'name' => 'Real Estate',
                'slug' => 'real-estate',
                'description' => 'Real Estate Agency, Property Management',
                'icon' => 'heroicon-o-home',
                'color' => '#10b981',
                'sort_order' => 14,
            ],
            [
                'name' => 'Home Services',
                'slug' => 'home-services',
                'description' => 'Cleaning Service, Home Repair, Plumbing, Electrical',
                'icon' => 'heroicon-o-wrench',
                'color' => '#64748b',
                'sort_order' => 15,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'General category for uncategorized businesses',
                'icon' => 'heroicon-o-ellipsis-horizontal',
                'color' => '#94a3b8',
                'sort_order' => 99,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
