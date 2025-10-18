<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@whatsml.com',
            'password' => Hash::make('admin123'),
            'phone' => '+1234567890',
            'company' => 'WhatsML',
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Regular Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'phone' => '+1234567891',
            'company' => 'Example Company',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Regular User
        User::create([
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => Hash::make('user123'),
            'phone' => '+1234567892',
            'company' => 'Test Company',
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Test User
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('test123'),
            'phone' => '+1234567893',
            'company' => 'Test Corp',
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
