<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create(
            [
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_password_changed' => 'no'
            ]
        );

        \App\Models\Information::factory()->create(
            [
                'last_name' => 'Admin',
                'first_name' => 'User',
                'gender' => 'female',
                'email_address' => 'admin@gmail.com',
            ]
        );

        \App\Models\Staff::factory()->create(
            [
                'user_id' => 1,
                'information_id' => 1,
                'staff_status' => 'active',
            ]
        );
    }
}
