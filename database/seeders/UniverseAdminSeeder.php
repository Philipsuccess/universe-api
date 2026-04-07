<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UniverseAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('UNIVERSE_ADMIN_EMAIL', 'owner@universe.app')],
            [
                'name' => env('UNIVERSE_ADMIN_NAME', 'Philip Kazah'),
                'matric_no' => 'ADMIN-0001',
                'referral_code' => 'UNIVERSE-ADMIN',
                'faculty' => 'Administration',
                'department' => 'Product Operations',
                'course' => 'Universe Control',
                'bio' => 'Primary administrator account for Universe.',
                'verified' => true,
                'role' => 'admin',
                'password' => Hash::make(env('UNIVERSE_ADMIN_PASSWORD', 'UniverseAdmin#2026')),
            ]
        );
    }
}
