<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create or update the super admin account from the configured credentials.
     */
    public function run(): void
    {
        throw_if(blank(config('auth.super_admin.password')), RuntimeException::class, 'Set SUPER_ADMIN_PASSWORD in .env before seeding the super admin.');

        $user = User::firstOrNew(['email' => config('auth.super_admin.email')]);

        $user->forceFill([
            'name' => config('auth.super_admin.name'),
            'password' => config('auth.super_admin.password'),
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }
}
