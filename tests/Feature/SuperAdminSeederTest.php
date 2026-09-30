<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;

beforeEach(function () {
    config()->set('auth.super_admin', [
        'name' => 'Super Admin',
        'email' => 'superadmin@example.com',
        'password' => 'secret-password',
    ]);
});

test('seeded super admin can log in', function () {
    $this->seed(SuperAdminSeeder::class);

    $user = User::sole();
    expect($user->email)->toBe('superadmin@example.com')
        ->and($user->isSuperAdmin())->toBeTrue();

    $this->post('/login', [
        'email' => 'superadmin@example.com',
        'password' => 'secret-password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('seeding again promotes the existing account without duplicating it', function () {
    User::factory()->create(['email' => 'superadmin@example.com']);

    $this->seed(SuperAdminSeeder::class);

    expect(User::sole()->role)->toBe(UserRole::SuperAdmin);
});

test('seeder refuses to run without a password', function () {
    config()->set('auth.super_admin.password', null);

    $this->seed(SuperAdminSeeder::class);
})->throws(RuntimeException::class);

test('registered users get the admin role', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::sole()->role)->toBe(UserRole::Admin);
});
