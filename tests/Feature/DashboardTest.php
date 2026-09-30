<?php

use App\Enums\LeadPlatform;
use App\Models\Lead;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the dashboard can show a period of the date received', function () {
    Lead::factory()->status('won')->manual(LeadPlatform::Upwork)->create(['name' => 'In Period', 'created_at' => '2026-09-15 10:00:00']);
    Lead::factory()->create(['name' => 'Also In Period', 'created_at' => '2026-09-01 00:00:00']);
    Lead::factory()->create(['name' => 'Before', 'created_at' => '2026-08-31 23:59:59']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['from' => '2026-09-01', 'to' => '2026-09-30']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['from' => '2026-09-01', 'to' => '2026-09-30'])
            ->where('totalLeads', 2)
            ->where('leadsByStatus.won', 1)
            ->where('leadsByStatus.new', 1)
            ->where('leadsByPlatform.upwork', 1)
            ->where('leadsByPlatform.website', 1)
            ->has('recentLeads', 2)
            ->where('recentLeads.0.name', 'In Period')
        );
});

test('the dashboard shows all time when no period is given or the dates are invalid', function () {
    Lead::factory()->count(3)->create();

    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['from' => null, 'to' => null])
            ->where('totalLeads', 3)
        );

    $this->actingAs($admin)
        ->get(route('dashboard', ['from' => 'last-week', 'to' => '2026-13-01']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['from' => null, 'to' => null])
            ->where('totalLeads', 3)
        );
});

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('the dashboard shows lead statistics', function () {
    Lead::factory()->count(2)->create();
    Lead::factory()->status('won')->manual(LeadPlatform::Upwork)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('totalLeads', 3)
            ->where('leadsByStatus.new', 2)
            ->where('leadsByStatus.won', 1)
            ->where('leadsByPlatform.website', 2)
            ->where('leadsByPlatform.upwork', 1)
            ->has('recentLeads', 3)
            ->has('recentLeads.0.platform')
        );
});
