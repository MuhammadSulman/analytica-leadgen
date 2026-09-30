<?php

use App\Enums\LeadPlatform;
use App\Models\Lead;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('landing page submissions are recorded as website leads', function () {
    $this->post(route('leads.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ])->assertRedirect();

    $lead = Lead::sole();

    expect($lead->source)->toBe('landing_page')
        ->and($lead->platform)->toBe(LeadPlatform::Website);
});

test('admins can add a lead manually with its platform', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.leads.store'), [
            'name' => 'upwork_client_42',
            'platform' => 'upwork',
            'status' => 'contacted',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $lead = Lead::sole();

    expect($lead->source)->toBe('manual')
        ->and($lead->platform)->toBe(LeadPlatform::Upwork)
        ->and($lead->status)->toBe('contacted')
        ->and($lead->email)->toBeNull();
});

test('manual leads require a valid platform', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.leads.store'), [
            'name' => 'Someone',
            'platform' => 'myspace',
            'status' => 'new',
        ])
        ->assertSessionHasErrors('platform');

    expect(Lead::count())->toBe(0);
});

test('admins can edit a lead', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.leads.update', $lead), [
            'name' => 'Renamed Client',
            'email' => 'renamed@example.com',
            'company' => 'New Co',
            'phone' => null,
            'message' => 'Moved the conversation to LinkedIn.',
            'platform' => 'linkedin',
            'status' => 'follow_up',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $lead->refresh();

    expect($lead->name)->toBe('Renamed Client')
        ->and($lead->email)->toBe('renamed@example.com')
        ->and($lead->company)->toBe('New Co')
        ->and($lead->phone)->toBeNull()
        ->and($lead->platform)->toBe(LeadPlatform::LinkedIn)
        ->and($lead->status)->toBe('follow_up')
        ->and($lead->source)->toBe('landing_page');
});

test('editing a lead validates its fields', function () {
    $lead = Lead::factory()->create(['name' => 'Original']);

    $this->actingAs(User::factory()->create())
        ->put(route('admin.leads.update', $lead), [
            'name' => '',
            'email' => 'not-an-email',
            'platform' => 'myspace',
            'status' => 'archived',
        ])
        ->assertSessionHasErrors(['name', 'email', 'platform', 'status']);

    expect($lead->refresh()->name)->toBe('Original');
});

test('admins can change a lead status', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.leads.status', $lead), ['status' => 'won'])
        ->assertRedirect();

    expect($lead->refresh()->status)->toBe('won');
});

test('guests cannot edit leads', function () {
    $lead = Lead::factory()->create(['name' => 'Original']);

    $this->put(route('admin.leads.update', $lead), [
        'name' => 'Hacked',
        'platform' => 'upwork',
        'status' => 'new',
    ])->assertRedirect(route('login'));

    expect($lead->refresh()->name)->toBe('Original');
});

test('admins can delete a lead', function () {
    $lead = Lead::factory()->create();
    $other = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.leads.destroy', $lead))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertSoftDeleted($lead);
    $this->assertNotSoftDeleted($other);
});

test('deleted leads are listed separately on the leads page', function () {
    Lead::factory()->create(['name' => 'Active']);
    Lead::factory()->create(['name' => 'Gone'])->delete();

    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.leads'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Leads')
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Active')
            ->where('filters.view', 'active')
            ->where('counts', ['active' => 1, 'deleted' => 1])
        );

    $this->actingAs($admin)
        ->get(route('admin.leads', ['view' => 'deleted']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Gone')
            ->where('filters.view', 'deleted')
            ->where('filters.sort', 'deleted_at')
        );
});

test('the leads list is paginated on the server', function () {
    Lead::factory()->count(25)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['page' => 3]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('leads.data', 5)
            ->where('leads.total', 25)
            ->where('leads.per_page', 10)
            ->where('leads.current_page', 3)
        );
});

test('the chosen page size is remembered for later visits', function () {
    Lead::factory()->count(25)->create();
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.leads', ['per_page' => 20]))
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 20));

    $this->actingAs($admin)
        ->get(route('admin.leads'))
        ->assertInertia(fn (Assert $page) => $page->where('leads.per_page', 20));
});

test('an unsupported page size falls back to the default', function () {
    Lead::factory()->count(15)->create();
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.leads', ['per_page' => 5000]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('leads.per_page', 10)->has('leads.data', 10));

    // A bad value must not overwrite a page size the admin chose earlier.
    $this->actingAs($admin)->get(route('admin.leads', ['per_page' => 50]));

    $this->actingAs($admin)
        ->get(route('admin.leads', ['per_page' => 'lots']))
        ->assertInertia(fn (Assert $page) => $page->where('leads.per_page', 50));
});

test('a page past the end redirects to the last page', function () {
    Lead::factory()->count(12)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['sort' => 'name', 'direction' => 'asc', 'page' => 5]))
        ->assertRedirect(route('admin.leads', ['sort' => 'name', 'direction' => 'asc', 'page' => 2]));
});

test('the date filter uses the time zone reported by the browser', function () {
    // 20:00 UTC on 10 Sep is 01:00 on 11 Sep in Karachi (UTC+5).
    Lead::factory()->create(['name' => 'Late Evening UTC', 'created_at' => '2026-09-10 20:00:00']);
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->withUnencryptedCookie('tz', 'Asia/Karachi')
        ->get(route('admin.leads', ['from' => '2026-09-11', 'to' => '2026-09-11']))
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 1));

    $this->actingAs($admin)
        ->withUnencryptedCookie('tz', 'Asia/Karachi')
        ->get(route('admin.leads', ['from' => '2026-09-10', 'to' => '2026-09-10']))
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 0));
});

test('the date filter falls back to UTC without a valid time zone', function () {
    Lead::factory()->create(['name' => 'Late Evening UTC', 'created_at' => '2026-09-10 20:00:00']);
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.leads', ['from' => '2026-09-10', 'to' => '2026-09-10']))
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 1));

    $this->actingAs($admin)
        ->withUnencryptedCookie('tz', 'Mars/Olympus_Mons')
        ->get(route('admin.leads', ['from' => '2026-09-10', 'to' => '2026-09-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 1));
});

test('the dashboard period uses the browser time zone', function () {
    Lead::factory()->create(['created_at' => '2026-09-10 20:00:00']);

    $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('tz', 'Asia/Karachi')
        ->get(route('dashboard', ['from' => '2026-09-11', 'to' => '2026-09-11']))
        ->assertInertia(fn (Assert $page) => $page->where('totalLeads', 1));
});

test('export shows received dates in the browser time zone', function () {
    Lead::factory()->create(['name' => 'Karachi Lead', 'created_at' => '2026-09-10 20:00:00']);

    $csv = $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('tz', 'Asia/Karachi')
        ->get(route('admin.leads.export'))
        ->streamedContent();

    expect($csv)->toContain('"2026-09-11 01:00:00"');
});

test('the leads list filters by the date received, inclusive of both days', function () {
    Lead::factory()->create(['name' => 'Before', 'created_at' => '2026-09-09 23:59:59']);
    Lead::factory()->create(['name' => 'First Day', 'created_at' => '2026-09-10 00:00:00']);
    Lead::factory()->create(['name' => 'Last Day', 'created_at' => '2026-09-20 23:59:59']);
    Lead::factory()->create(['name' => 'After', 'created_at' => '2026-09-21 00:00:00']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['from' => '2026-09-10', 'to' => '2026-09-20']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('leads.data', 2)
            ->where('leads.data.0.name', 'Last Day')
            ->where('leads.data.1.name', 'First Day')
            ->where('filters.from', '2026-09-10')
            ->where('filters.to', '2026-09-20')
        );
});

test('the date filter works with only one end of the range', function () {
    Lead::factory()->create(['name' => 'Old', 'created_at' => '2026-01-01 10:00:00']);
    Lead::factory()->create(['name' => 'Recent', 'created_at' => '2026-09-01 10:00:00']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['from' => '2026-06-01']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Recent')
            ->where('filters.to', null)
        );
});

test('a backwards date range is swapped', function () {
    Lead::factory()->create(['name' => 'Inside', 'created_at' => '2026-09-15 12:00:00']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['from' => '2026-09-20', 'to' => '2026-09-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('leads.data', 1)
            ->where('filters.from', '2026-09-10')
            ->where('filters.to', '2026-09-20')
        );
});

test('invalid dates are ignored', function () {
    Lead::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['from' => '2026-02-30', 'to' => 'yesterday']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('leads.data', 2)
            ->where('filters.from', null)
            ->where('filters.to', null)
        );
});

test('export applies the date range', function () {
    Lead::factory()->create(['name' => 'In Range', 'created_at' => '2026-09-15 12:00:00']);
    Lead::factory()->create(['name' => 'Out Of Range', 'created_at' => '2026-08-01 12:00:00']);

    $csv = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export', ['from' => '2026-09-01', 'to' => '2026-09-30']))
        ->streamedContent();

    expect($csv)->toContain('In Range')->not->toContain('Out Of Range');
});

test('the leads list applies search and filters on the server', function () {
    Lead::factory()->manual(LeadPlatform::Upwork)->status('won')->create(['name' => 'Acme Upwork Won']);
    Lead::factory()->manual(LeadPlatform::Upwork)->status('lost')->create(['name' => 'Acme Upwork Lost']);
    Lead::factory()->manual(LeadPlatform::Fiverr)->status('won')->create(['name' => 'Acme Fiverr Won']);
    Lead::factory()->manual(LeadPlatform::Upwork)->status('won')->create(['name' => 'Other Upwork Won', 'company' => 'Zeta']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', [
            'search' => 'acme',
            'platforms' => ['upwork'],
            'statuses' => ['won'],
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Acme Upwork Won')
            ->where('filters.search', 'acme')
            ->where('filters.platforms', ['upwork'])
            ->where('filters.statuses', ['won'])
        );
});

test('the leads list can be sorted by name', function () {
    Lead::factory()->create(['name' => 'Charlie']);
    Lead::factory()->create(['name' => 'Alice']);
    Lead::factory()->create(['name' => 'Bob']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('leads.data.0.name', 'Alice')
            ->where('leads.data.1.name', 'Bob')
            ->where('leads.data.2.name', 'Charlie')
        );
});

test('the leads list shows the newest leads first by default', function () {
    Lead::factory()->create(['name' => 'Old', 'created_at' => now()->subDays(3)]);
    Lead::factory()->create(['name' => 'New', 'created_at' => now()]);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('leads.data.0.name', 'New')
            ->where('filters.sort', 'created_at')
            ->where('filters.direction', 'desc')
        );
});

test('invalid list options fall back to the defaults', function () {
    Lead::factory()->create(['name' => 'Old', 'created_at' => now()->subDay()]);
    Lead::factory()->create(['name' => 'New']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', [
            'view' => 'archive',
            'sort' => 'password',
            'direction' => 'sideways',
            'search' => ['not', 'a', 'string'],
            'platforms' => 'upwork',
            'statuses' => ['won', 'bogus'],
            'page' => 'abc',
        ]))
        ->assertOk()
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', [
                'view' => 'active',
                'search' => '',
                'platforms' => [],
                'statuses' => ['won'],
                'from' => null,
                'to' => null,
                'sort' => 'created_at',
                'direction' => 'desc',
            ])
            ->where('leads.current_page', 1)
        );
});

test('an overly long search is trimmed rather than rejected', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads', ['search' => str_repeat('a', 300)]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.search', str_repeat('a', 255)));
});

test('deleted leads are left out of the dashboard', function () {
    Lead::factory()->count(2)->create();
    Lead::factory()->create()->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalLeads', 2)
            ->has('recentLeads', 2)
        );
});

test('admins can restore a deleted lead', function () {
    $lead = Lead::factory()->create();
    $lead->delete();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.leads.restore', $lead))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertNotSoftDeleted($lead);
});

test('admins can permanently delete a deleted lead', function () {
    $lead = Lead::factory()->create();
    $lead->delete();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.leads.force-destroy', $lead))
        ->assertRedirect();

    $this->assertModelMissing($lead);
});

test('active leads cannot be permanently deleted directly', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.leads.force-destroy', $lead))
        ->assertNotFound();

    $this->assertNotSoftDeleted($lead);
});

test('deleted leads cannot be edited', function () {
    $lead = Lead::factory()->create();
    $lead->delete();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.leads.update', $lead), [
            'name' => 'Changed',
            'platform' => 'upwork',
            'status' => 'new',
        ])
        ->assertNotFound();
});

test('guests cannot delete leads', function () {
    $lead = Lead::factory()->create();

    $this->delete(route('admin.leads.destroy', $lead))
        ->assertRedirect(route('login'));

    $this->assertNotSoftDeleted($lead);
});

test('admins can bulk delete leads', function () {
    [$first, $second, $kept] = Lead::factory()->count(3)->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.leads.bulk-destroy'), ['ids' => [$first->id, $second->id]])
        ->assertRedirect()
        ->assertSessionHas('success', '2 leads moved to Deleted.');

    $this->assertSoftDeleted($first);
    $this->assertSoftDeleted($second);
    $this->assertNotSoftDeleted($kept);
});

test('admins can bulk change lead status', function () {
    [$first, $second, $untouched] = Lead::factory()->count(3)->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.leads.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'status' => 'contacted',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', '2 leads updated.');

    expect($first->refresh()->status)->toBe('contacted')
        ->and($second->refresh()->status)->toBe('contacted')
        ->and($untouched->refresh()->status)->toBe('new');
});

test('bulk status change skips deleted leads', function () {
    $active = Lead::factory()->create();
    $deleted = Lead::factory()->create();
    $deleted->delete();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.leads.bulk-status'), [
            'ids' => [$active->id, $deleted->id],
            'status' => 'won',
        ])
        ->assertSessionHas('success', '1 lead updated.');

    expect($active->refresh()->status)->toBe('won')
        ->and(Lead::withTrashed()->find($deleted->id)->status)->toBe('new');
});

test('bulk status change requires a valid status', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.leads.bulk-status'), [
            'ids' => [$lead->id],
            'status' => 'archived',
        ])
        ->assertSessionHasErrors('status');

    expect($lead->refresh()->status)->toBe('new');
});

test('admins can bulk restore deleted leads', function () {
    [$first, $second, $stillDeleted] = Lead::factory()->count(3)->create();
    Lead::query()->delete();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.leads.bulk-restore'), ['ids' => [$first->id, $second->id]])
        ->assertSessionHas('success', '2 leads restored.');

    $this->assertNotSoftDeleted($first);
    $this->assertNotSoftDeleted($second);
    $this->assertSoftDeleted($stillDeleted);
});

test('bulk permanent delete only removes leads already in Deleted', function () {
    $deleted = Lead::factory()->create();
    $deleted->delete();
    $active = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.leads.bulk-force-destroy'), ['ids' => [$deleted->id, $active->id]])
        ->assertSessionHas('success', '1 lead permanently deleted.');

    $this->assertModelMissing($deleted);
    $this->assertNotSoftDeleted($active);
});

test('bulk actions require a list of lead ids', function () {
    $this->actingAs(User::factory()->create())
        ->delete(route('admin.leads.bulk-destroy'), ['ids' => []])
        ->assertSessionHasErrors('ids');

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.leads.bulk-destroy'), ['ids' => ['abc']])
        ->assertSessionHasErrors('ids.0');
});

test('guests cannot bulk delete leads', function () {
    $lead = Lead::factory()->create();

    $this->delete(route('admin.leads.bulk-destroy'), ['ids' => [$lead->id]])
        ->assertRedirect(route('login'));

    $this->assertNotSoftDeleted($lead);
});

test('admins can export active leads as CSV', function () {
    Lead::factory()->manual(LeadPlatform::LinkedIn)->status('follow_up')->create([
        'name' => 'Ana Müller',
        'email' => 'ana@example.com',
    ]);
    Lead::factory()->create(['name' => 'Deleted Person'])->delete();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export'))
        ->assertOk()
        ->assertDownload('leads-'.now()->format('Y-m-d').'.csv');

    $csv = $response->streamedContent();

    expect($csv)->toStartWith("\xEF\xBB\xBFID,Name,Email,Company,Phone,Platform,Status,Source,Message,Received")
        ->toContain('"Ana Müller",ana@example.com')
        ->toContain('LinkedIn,"Follow Up",Manual')
        ->not->toContain('Deleted Person');
});

test('export can be limited to selected leads', function () {
    $picked = Lead::factory()->create(['name' => 'Picked']);
    Lead::factory()->create(['name' => 'Skipped']);

    $csv = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export', ['ids' => [$picked->id]]))
        ->streamedContent();

    expect($csv)->toContain('Picked')->not->toContain('Skipped');
});

test('export applies the search term like the leads page does', function () {
    Lead::factory()->create(['name' => 'Alice', 'company' => 'Acme Corp']);
    Lead::factory()->create(['name' => 'Bob', 'email' => 'bob@acme.io', 'company' => 'Other']);
    Lead::factory()->create(['name' => 'Carol', 'email' => 'carol@example.com', 'company' => 'Zeta']);

    $csv = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export', ['search' => 'ACME']))
        ->streamedContent();

    expect($csv)->toContain('Alice')->toContain('Bob')->not->toContain('Carol');
});

test('export search treats wildcard characters literally', function () {
    Lead::factory()->create(['name' => '100% Real', 'company' => null]);
    Lead::factory()->create(['name' => 'Someone Else', 'company' => null]);

    $csv = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export', ['search' => '%']))
        ->streamedContent();

    expect($csv)->toContain('100% Real')->not->toContain('Someone Else');
});

test('export applies platform and status filters', function () {
    Lead::factory()->manual(LeadPlatform::Upwork)->status('won')->create(['name' => 'Upwork Won']);
    Lead::factory()->manual(LeadPlatform::Upwork)->status('lost')->create(['name' => 'Upwork Lost']);
    Lead::factory()->manual(LeadPlatform::Fiverr)->status('won')->create(['name' => 'Fiverr Won']);
    Lead::factory()->status('won')->create(['name' => 'Website Won']);

    $csv = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export', [
            'platforms' => ['upwork', 'fiverr'],
            'statuses' => ['won'],
        ]))
        ->streamedContent();

    expect($csv)->toContain('Upwork Won')
        ->toContain('Fiverr Won')
        ->not->toContain('Upwork Lost')
        ->not->toContain('Website Won');
});

test('export ignores unknown filter values', function () {
    Lead::factory()->manual(LeadPlatform::Upwork)->create(['name' => 'Upwork Lead']);
    Lead::factory()->create(['name' => 'Website Lead']);

    $csv = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export', ['platforms' => ['myspace', 'upwork'], 'ids' => 'nope']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('Upwork Lead')->not->toContain('Website Lead');
});

test('export neutralises spreadsheet formulas', function () {
    Lead::factory()->create(['name' => '=HYPERLINK("http://evil.test")']);

    $csv = $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.export'))
        ->streamedContent();

    expect($csv)->toContain('"\'=HYPERLINK(""http://evil.test"")"');
});

test('guests cannot export leads', function () {
    $this->get(route('admin.leads.export'))->assertRedirect(route('login'));
});

test('guests cannot restore leads', function () {
    $lead = Lead::factory()->create();
    $lead->delete();

    $this->patch(route('admin.leads.restore', $lead))
        ->assertRedirect(route('login'));

    $this->assertSoftDeleted($lead);
});

test('guests cannot add manual leads', function () {
    $this->post(route('admin.leads.store'), [
        'name' => 'Someone',
        'platform' => 'upwork',
        'status' => 'new',
    ])->assertRedirect(route('login'));

    expect(Lead::count())->toBe(0);
});
