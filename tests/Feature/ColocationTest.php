<?php

use App\Models\Colocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────

function createColocationWithOwner(?User $owner = null): array
{
    $owner = $owner ?? User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);
    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

    return [$owner, $colocation];
}

function addMember(Colocation $colocation, ?User $member = null): User
{
    $member = $member ?? User::factory()->create();
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    return $member;
}

// ─── Index ─────────────────────────────────────────────────────────────

test('guest is redirected to login on colocation index', function () {
    $this->get(route('colocations.index'))
        ->assertRedirect(route('login'));
});

test('user sees their colocations on index', function () {
    [$owner, $colocation] = createColocationWithOwner();

    $this->actingAs($owner)
        ->get(route('colocations.index'))
        ->assertOk()
        ->assertSee($colocation->name);
});

// ─── Create ────────────────────────────────────────────────────────────

test('authenticated user can view create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('colocations.create'))
        ->assertOk();
});

test('user with active colocation is redirected from create form', function () {
    [$owner, $colocation] = createColocationWithOwner();

    $this->actingAs($owner)
        ->get(route('colocations.create'))
        ->assertRedirect(route('colocations.index'));
});

// ─── Store ─────────────────────────────────────────────────────────────

test('user can create colocation and is auto-added as owner', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('colocations.store'), [
        'name' => 'Appartement Paris',
        'description' => 'Notre coloc',
    ]);

    $colocation = Colocation::first();
    $response->assertRedirect(route('colocations.show', $colocation));

    expect($colocation->name)->toBe('Appartement Paris');
    expect($colocation->owner_id)->toBe($user->id);
    expect($colocation->status)->toBe('active');

    // User is added to pivot as owner
    $pivot = $colocation->members()->where('user_id', $user->id)->first();
    expect($pivot)->not->toBeNull();
    expect($pivot->pivot->role)->toBe('owner');
});

test('user with active colocation cannot create another', function () {
    [$owner, $colocation] = createColocationWithOwner();

    $this->actingAs($owner)->post(route('colocations.store'), [
        'name' => 'Second coloc',
    ])->assertRedirect(route('colocations.index'));

    expect(Colocation::count())->toBe(1);
});

test('store validation requires name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('colocations.store'), [
        'name' => '',
    ])->assertSessionHasErrors('name');
});

// ─── Show ──────────────────────────────────────────────────────────────

test('member can view colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);

    $this->actingAs($member)
        ->get(route('colocations.show', $colocation))
        ->assertOk()
        ->assertSee($colocation->name);
});

test('non-member cannot view colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('colocations.show', $colocation))
        ->assertForbidden();
});

// ─── Edit ──────────────────────────────────────────────────────────────

test('owner can view edit form', function () {
    [$owner, $colocation] = createColocationWithOwner();

    $this->actingAs($owner)
        ->get(route('colocations.edit', $colocation))
        ->assertOk();
});

test('non-owner cannot view edit form', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);

    $this->actingAs($member)
        ->get(route('colocations.edit', $colocation))
        ->assertForbidden();
});

// ─── Update ────────────────────────────────────────────────────────────

test('owner can update colocation name and description', function () {
    [$owner, $colocation] = createColocationWithOwner();

    $this->actingAs($owner)
        ->put(route('colocations.update', $colocation), [
            'name' => 'Updated Name',
            'description' => 'Updated desc',
        ])
        ->assertRedirect(route('colocations.show', $colocation));

    expect($colocation->fresh()->name)->toBe('Updated Name');
    expect($colocation->fresh()->description)->toBe('Updated desc');
});

test('non-owner cannot update colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);

    $this->actingAs($member)
        ->put(route('colocations.update', $colocation), [
            'name' => 'Hijacked',
        ])
        ->assertForbidden();
});

// ─── Destroy (Cancel) ──────────────────────────────────────────────────

test('owner can cancel colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);

    $this->actingAs($owner)
        ->delete(route('colocations.destroy', $colocation))
        ->assertRedirect(route('colocations.index'));

    $colocation->refresh();
    expect($colocation->status)->toBe('cancelled');

    // All members should have left_at set
    $ownerPivot = $colocation->members()->where('user_id', $owner->id)->first();
    $memberPivot = $colocation->members()->where('user_id', $member->id)->first();
    expect($ownerPivot->pivot->left_at)->not->toBeNull();
    expect($memberPivot->pivot->left_at)->not->toBeNull();
});

test('non-owner cannot cancel colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);

    $this->actingAs($member)
        ->delete(route('colocations.destroy', $colocation))
        ->assertForbidden();
});

test('already cancelled colocation cannot be cancelled again', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $colocation->update(['status' => 'cancelled']);

    $this->actingAs($owner)
        ->delete(route('colocations.destroy', $colocation))
        ->assertForbidden();
});

// ─── Leave ─────────────────────────────────────────────────────────────

test('member can leave colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);

    $this->actingAs($member)
        ->post(route('colocations.leave', $colocation))
        ->assertRedirect(route('colocations.index'));

    $memberPivot = $colocation->members()->where('user_id', $member->id)->first();
    expect($memberPivot->pivot->left_at)->not->toBeNull();
});

test('owner cannot leave colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();

    $this->actingAs($owner)
        ->post(route('colocations.leave', $colocation))
        ->assertForbidden();
});

test('non-member cannot leave colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('colocations.leave', $colocation))
        ->assertForbidden();
});

// ─── Remove Member ─────────────────────────────────────────────────────

test('owner can remove a member', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);

    $this->actingAs($owner)
        ->post(route('colocations.removeMember', [$colocation, $member]))
        ->assertRedirect(route('colocations.show', $colocation));

    $memberPivot = $colocation->members()->where('user_id', $member->id)->first();
    expect($memberPivot->pivot->left_at)->not->toBeNull();
});

test('owner cannot remove themselves', function () {
    [$owner, $colocation] = createColocationWithOwner();

    $this->actingAs($owner)
        ->post(route('colocations.removeMember', [$colocation, $owner]))
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('error');
});

test('non-owner cannot remove members', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);
    $anotherMember = addMember($colocation);

    $this->actingAs($member)
        ->post(route('colocations.removeMember', [$colocation, $anotherMember]))
        ->assertForbidden();
});

test('cannot remove member from cancelled colocation', function () {
    [$owner, $colocation] = createColocationWithOwner();
    $member = addMember($colocation);
    $colocation->update(['status' => 'cancelled']);

    $this->actingAs($owner)
        ->post(route('colocations.removeMember', [$colocation, $member]))
        ->assertForbidden();
});
