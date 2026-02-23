<?php

use App\Models\Colocation;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────

function setupColocationForReputation(): array
{
    $owner = User::factory()->create(['reputation' => 0]);
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);
    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

    $member = User::factory()->create(['reputation' => 0]);
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    return [$owner, $member, $colocation];
}

// ─── Leave ─────────────────────────────────────────────────────────────

test('member leaving without debt gains +1 reputation', function () {
    [$owner, $member, $colocation] = setupColocationForReputation();

    $this->actingAs($member)
        ->post(route('colocations.leave', $colocation));

    expect($member->fresh()->reputation)->toBe(1);
});

test('member leaving with unpaid debt loses -1 reputation', function () {
    [$owner, $member, $colocation] = setupColocationForReputation();

    // Member owes owner money
    Settlement::factory()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
        'is_paid' => false,
    ]);

    $this->actingAs($member)
        ->post(route('colocations.leave', $colocation));

    expect($member->fresh()->reputation)->toBe(-1);
});

// ─── Remove Member ─────────────────────────────────────────────────────

test('removed member without debt gains +1 reputation', function () {
    [$owner, $member, $colocation] = setupColocationForReputation();

    $this->actingAs($owner)
        ->post(route('colocations.removeMember', [$colocation, $member]));

    expect($member->fresh()->reputation)->toBe(1);
});

test('removed member with unpaid debt loses -1 reputation', function () {
    [$owner, $member, $colocation] = setupColocationForReputation();

    Settlement::factory()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
        'is_paid' => false,
    ]);

    $this->actingAs($owner)
        ->post(route('colocations.removeMember', [$colocation, $member]));

    expect($member->fresh()->reputation)->toBe(-1);
});

// ─── Cancel Colocation ─────────────────────────────────────────────────

test('owner cancelling without any debt gains +1 reputation', function () {
    [$owner, $member, $colocation] = setupColocationForReputation();

    $this->actingAs($owner)
        ->delete(route('colocations.destroy', $colocation));

    expect($owner->fresh()->reputation)->toBe(1);
});

test('owner cancelling with own unpaid debt loses -1 reputation', function () {
    [$owner, $member, $colocation] = setupColocationForReputation();

    Settlement::factory()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $owner->id,
        'to_user_id' => $member->id,
        'is_paid' => false,
    ]);

    $this->actingAs($owner)
        ->delete(route('colocations.destroy', $colocation));

    expect($owner->fresh()->reputation)->toBe(-1);
});

// ─── Edge Cases ────────────────────────────────────────────────────────

test('paid settlements do not count as debt', function () {
    [$owner, $member, $colocation] = setupColocationForReputation();

    // Settlement exists but is paid
    Settlement::factory()->paid()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
    ]);

    $this->actingAs($member)
        ->post(route('colocations.leave', $colocation));

    expect($member->fresh()->reputation)->toBe(1); // no unpaid debt → +1
});

test('reputation accumulates over multiple departures', function () {
    $user = User::factory()->create(['reputation' => 5]);

    // First colocation — leave clean
    $col1 = Colocation::factory()->create(['owner_id' => User::factory()->create()->id]);
    $col1->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($user)
        ->post(route('colocations.leave', $col1));

    expect($user->fresh()->reputation)->toBe(6); // 5 + 1

    // Second colocation — leave with debt
    $col2 = Colocation::factory()->create(['owner_id' => User::factory()->create()->id]);
    $col2->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    Settlement::factory()->create([
        'colocation_id' => $col2->id,
        'from_user_id' => $user->id,
        'to_user_id' => $col2->owner_id,
        'is_paid' => false,
    ]);

    $this->actingAs($user)
        ->post(route('colocations.leave', $col2));

    expect($user->fresh()->reputation)->toBe(5); // 6 - 1
});
