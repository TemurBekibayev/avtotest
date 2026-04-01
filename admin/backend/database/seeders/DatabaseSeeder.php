<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
        ['email' => 'admin@eavtotalim.uz'],
        [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]
        );

        $org = \App\Models\Organization::firstOrCreate(
        ['name' => 'IT Markaz Auto School'],
        [
            'address' => 'Amudaryo, Uzbekistan',
            'phone' => '+998901234567',
        ]
        );

        $group = \App\Models\Group::firstOrCreate(
        ['name' => 'Group 19 (BC)', 'organization_id' => $org->id],
        [
            'category' => 'BC',
        ]
        );

        $group2 = \App\Models\Group::firstOrCreate(
        ['name' => 'Group 20 (B)', 'organization_id' => $org->id],
        [
            'category' => 'B',
        ]
        );

        // Student 1
        $user1 = User::firstOrCreate(
        ['email' => 'student1@test.uz'],
        [
            'name' => 'Rasulov Isoxon',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]
        );

        \App\Models\Student::updateOrCreate(
        ['full_name' => 'Rasulov Isoxon'],
        [
            'user_id' => $user1->id,
            'organization_id' => $org->id,
            'group_id' => $group->id,
            'category' => 'BC',
            'phone' => '+998901234567',
            'status' => 'active',
        ]
        );

        // Student 2
        $user2 = User::firstOrCreate(
        ['email' => 'student2@test.uz'],
        [
            'name' => 'Valijonov Sanjarbek',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]
        );

        \App\Models\Student::updateOrCreate(
        ['full_name' => 'Valijonov Sanjarbek'],
        [
            'user_id' => $user2->id,
            'organization_id' => $org->id,
            'group_id' => $group2->id,
            'category' => 'B',
            'phone' => '+998919876543',
            'status' => 'inactive',
        ]
        );

        for ($i = 1; $i <= 20; $i++) {
            $template = \App\Models\TestTemplate::firstOrCreate(
            ['name' => "$i SHABLON"],
            [
                'type' => 'Theory',
                'duration_minutes' => 25,
                'passing_score' => 90,
            ]
            );

            if ($template->questions()->count() === 0) {
                // Get 20 random questions
                $questions = \App\Models\TestQuestion::inRandomOrder()->limit(20)->pluck('id');
                if ($questions->count() > 0) {
                    $template->questions()->attach($questions);
                }
            }
        }
    }
}
