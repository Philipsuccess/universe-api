<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UniverseAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => env('UNIVERSE_ADMIN_NAME', 'Philip Kazah'),
                'email' => env('UNIVERSE_ADMIN_EMAIL', 'Archii101@universe.app'),
                'password' => env('UNIVERSE_ADMIN_PASSWORD', 'Universe#Archii4life'),
                'matric_no' => 'ADMIN-0001',
                'referral_code' => 'UNIVERSE-ADMIN',
                'department' => 'Product Operations',
                'bio' => 'Primary administrator account for Universe.',
            ],
            [
                'name' => env('UNIVERSE_ADMIN_TWO_NAME', 'Stephen Dev'),
                'email' => env('UNIVERSE_ADMIN_TWO_EMAIL', 'StephenDev@universe.app'),
                'password' => env('UNIVERSE_ADMIN_TWO_PASSWORD', 'Universe#dev4life'),
                'matric_no' => 'ADMIN-0002',
                'referral_code' => 'UNIVERSE-OPS',
                'department' => 'Support Operations',
                'bio' => 'Secondary administrator account for Universe.',
            ],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'matric_no' => $admin['matric_no'],
                    'referral_code' => $admin['referral_code'],
                    'faculty' => 'Administration',
                    'department' => $admin['department'],
                    'course' => 'Universe Control',
                    'bio' => $admin['bio'],
                    'verified' => true,
                    'role' => 'admin',
                    'password' => Hash::make($admin['password']),
                ]
            );
        }
    }
}
