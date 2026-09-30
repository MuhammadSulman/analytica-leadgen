<?php

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins can view a lead with its notes, newest first', function () {
    $lead = Lead::factory()->create(['name' => 'Jane Client']);
    $author = User::factory()->create(['name' => 'Sam Admin']);

    LeadNote::factory()->for($lead)->for($author)->create(['body' => 'First call', 'created_at' => now()->subDay()]);
    LeadNote::factory()->for($lead)->for($author)->create(['body' => 'Sent proposal', 'created_at' => now()]);
    LeadNote::factory()->create(['body' => 'Note on another lead']);

    $this->actingAs($author)
        ->get(route('admin.leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/LeadShow')
            ->where('lead.name', 'Jane Client')
            ->has('notes', 2)
            ->where('notes.0.body', 'Sent proposal')
            ->where('notes.0.author', 'Sam Admin')
            ->where('notes.0.can_delete', true)
            ->where('notes.1.body', 'First call')
        );
});

test('deleted leads can still be viewed', function () {
    $lead = Lead::factory()->create();
    $lead->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->whereNot('lead.deleted_at', null));
});

test('admins can add a note to a lead', function () {
    $lead = Lead::factory()->create();
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.leads.notes.store', $lead), ['body' => "Called the client.\nThey want a quote."])
        ->assertRedirect()
        ->assertSessionHas('success', 'Note added.');

    $note = LeadNote::sole();

    expect($note->lead_id)->toBe($lead->id)
        ->and($note->user_id)->toBe($admin->id)
        ->and($note->body)->toBe("Called the client.\nThey want a quote.");
});

test('a note needs some text', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.leads.notes.store', $lead), ['body' => '   '])
        ->assertSessionHasErrors('body');

    expect(LeadNote::count())->toBe(0);
});

test('notes cannot be added to deleted leads', function () {
    $lead = Lead::factory()->create();
    $lead->delete();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.leads.notes.store', $lead), ['body' => 'Too late'])
        ->assertNotFound();
});

test('admins can delete their own notes', function () {
    $admin = User::factory()->create();
    $note = LeadNote::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->delete(route('admin.leads.notes.destroy', [$note->lead_id, $note]))
        ->assertRedirect();

    $this->assertModelMissing($note);
});

test("admins cannot delete another admin's note", function () {
    $note = LeadNote::factory()->create();
    $otherAdmin = User::factory()->create();

    $this->actingAs($otherAdmin)
        ->get(route('admin.leads.show', $note->lead_id))
        ->assertInertia(fn (Assert $page) => $page->where('notes.0.can_delete', false));

    $this->actingAs($otherAdmin)
        ->delete(route('admin.leads.notes.destroy', [$note->lead_id, $note]))
        ->assertForbidden();

    $this->assertModelExists($note);
});

test("a super admin can delete anyone's note", function () {
    $note = LeadNote::factory()->create();
    $superAdmin = User::factory()->create();
    $superAdmin->forceFill(['role' => UserRole::SuperAdmin])->save();

    $this->actingAs($superAdmin)
        ->delete(route('admin.leads.notes.destroy', [$note->lead_id, $note]))
        ->assertRedirect();

    $this->assertModelMissing($note);
});

test('a note can only be deleted through its own lead', function () {
    $admin = User::factory()->create();
    $note = LeadNote::factory()->for($admin)->create();
    $otherLead = Lead::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.leads.notes.destroy', [$otherLead, $note]))
        ->assertNotFound();

    $this->assertModelExists($note);
});

test('notes are kept when a lead is soft deleted and removed when it is permanently deleted', function () {
    $note = LeadNote::factory()->create();
    $lead = $note->lead;

    $lead->delete();
    $this->assertModelExists($note);

    $lead->forceDelete();
    $this->assertModelMissing($note);
});

test("a note stays when its author's account is removed", function () {
    $author = User::factory()->create();
    $note = LeadNote::factory()->for($author)->create();

    $author->delete();

    expect($note->refresh()->user_id)->toBeNull();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.leads.show', $note->lead_id))
        ->assertInertia(fn (Assert $page) => $page->where('notes.0.author', null));
});

test('guests cannot view leads or add notes', function () {
    $lead = Lead::factory()->create();

    $this->get(route('admin.leads.show', $lead))->assertRedirect(route('login'));
    $this->post(route('admin.leads.notes.store', $lead), ['body' => 'Hi'])->assertRedirect(route('login'));

    expect(LeadNote::count())->toBe(0);
});
