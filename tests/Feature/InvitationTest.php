<?php

use App\Models\Colocation;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────

function createColocationWithOwnerForInvite(?User $owner = null): array
{
    $owner = $owner ?? User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);
    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

    return [$owner, $colocation];
}

// ─── Send Invitation (store) ───────────────────────────────────────────

test('owner can send invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    $this->actingAs($owner)
        ->post(route('invitations.store', $colocation), [
            'email' => 'invitee@example.com',
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect(Invitation::where('email', 'invitee@example.com')->count())->toBe(1);
    expect(Invitation::first()->status)->toBe('pending');
});

test('non-owner cannot send invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();
    $member = User::factory()->create();
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($member)
        ->post(route('invitations.store', $colocation), [
            'email' => 'invitee@example.com',
        ])
        ->assertForbidden();
});

test('cannot send invitation to existing active member', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();
    $member = User::factory()->create();
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($owner)
        ->post(route('invitations.store', $colocation), [
            'email' => $member->email,
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('error');

    expect(Invitation::count())->toBe(0);
});

test('cannot send invitation to user with active colocation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    // Create another user who already has an active colocation
    $busyUser = User::factory()->create();
    $otherColocation = Colocation::factory()->create(['owner_id' => $busyUser->id]);
    $otherColocation->members()->attach($busyUser->id, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($owner)
        ->post(route('invitations.store', $colocation), [
            'email' => $busyUser->email,
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('error');

    expect(Invitation::count())->toBe(0);
});

test('cannot send duplicate pending invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
        'status' => 'pending',
    ]);

    $this->actingAs($owner)
        ->post(route('invitations.store', $colocation), [
            'email' => 'invitee@example.com',
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('error');

    expect(Invitation::count())->toBe(1);
});

test('cannot send invitation on cancelled colocation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();
    $colocation->update(['status' => 'cancelled']);

    $this->actingAs($owner)
        ->post(route('invitations.store', $colocation), [
            'email' => 'invitee@example.com',
        ])
        ->assertForbidden();
});

// ─── Accept Invitation ─────────────────────────────────────────────────

test('user can accept invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect($invitation->fresh()->status)->toBe('accepted');
    expect($colocation->activeMembers()->where('user_id', $invitee->id)->exists())->toBeTrue();
});

test('user cannot accept invitation meant for another email', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($wrongUser)
        ->get(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($invitation->fresh()->status)->toBe('pending');
});

test('user with active colocation cannot accept invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    // Invitee already has a colocation
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $otherColocation = Colocation::factory()->create(['owner_id' => $invitee->id]);
    $otherColocation->members()->attach($invitee->id, ['role' => 'owner', 'joined_at' => now()]);

    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($invitation->fresh()->status)->toBe('pending');
});

test('cannot accept invitation for cancelled colocation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();
    $colocation->update(['status' => 'cancelled']);

    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($invitation->fresh()->status)->toBe('refused');
});

// ─── Refuse Invitation ─────────────────────────────────────────────────

test('user can refuse invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($invitee)
        ->get(route('invitations.refuse', $invitation->token))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($invitation->fresh()->status)->toBe('refused');
});

test('user cannot refuse invitation meant for another email', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($wrongUser)
        ->get(route('invitations.refuse', $invitation->token))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($invitation->fresh()->status)->toBe('pending');
});

// ─── Cancel Invitation (destroy) ───────────────────────────────────────

test('owner can cancel pending invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($owner)
        ->delete(route('invitations.destroy', $invitation))
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect(Invitation::count())->toBe(0);
});

test('non-owner cannot cancel invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();
    $member = User::factory()->create();
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $invitation = Invitation::factory()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($member)
        ->delete(route('invitations.destroy', $invitation))
        ->assertForbidden();

    expect(Invitation::count())->toBe(1);
});

test('owner cannot cancel already accepted invitation', function () {
    [$owner, $colocation] = createColocationWithOwnerForInvite();

    $invitation = Invitation::factory()->accepted()->create([
        'colocation_id' => $colocation->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($owner)
        ->delete(route('invitations.destroy', $invitation))
        ->assertForbidden();

    expect(Invitation::count())->toBe(1);
});
