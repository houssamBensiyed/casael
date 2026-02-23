<?php

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Invitation;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Colocation Relationships ──────────────────────────────────────────

test('colocation belongs to an owner', function () {
    $owner = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);

    expect($colocation->owner->id)->toBe($owner->id);
});

test('colocation has many members through pivot', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);

    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    expect($colocation->members)->toHaveCount(2);
    expect($colocation->members->pluck('id')->toArray())->toContain($owner->id, $member->id);
});

test('colocation activeMembers excludes users with left_at set', function () {
    $owner = User::factory()->create();
    $activeMember = User::factory()->create();
    $leftMember = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);

    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);
    $colocation->members()->attach($activeMember->id, ['role' => 'member', 'joined_at' => now()]);
    $colocation->members()->attach($leftMember->id, ['role' => 'member', 'joined_at' => now(), 'left_at' => now()]);

    expect($colocation->activeMembers)->toHaveCount(2);
    expect($colocation->activeMembers->pluck('id')->toArray())->not->toContain($leftMember->id);
});

test('colocation has many invitations', function () {
    $colocation = Colocation::factory()->create();
    Invitation::factory()->count(3)->create(['colocation_id' => $colocation->id]);

    expect($colocation->invitations)->toHaveCount(3);
});

test('colocation has many categories', function () {
    $colocation = Colocation::factory()->create();
    Category::factory()->count(4)->create(['colocation_id' => $colocation->id]);

    expect($colocation->categories)->toHaveCount(4);
});

test('colocation has many expenses', function () {
    $colocation = Colocation::factory()->create();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);
    $payer = User::factory()->create();

    Expense::factory()->count(5)->create([
        'colocation_id' => $colocation->id,
        'category_id' => $category->id,
        'payer_id' => $payer->id,
    ]);

    expect($colocation->expenses)->toHaveCount(5);
});

test('colocation has many settlements', function () {
    $colocation = Colocation::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Settlement::factory()->count(2)->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $user1->id,
        'to_user_id' => $user2->id,
    ]);

    expect($colocation->settlements)->toHaveCount(2);
});

test('colocation active scope filters correctly', function () {
    Colocation::factory()->create(['status' => 'active']);
    Colocation::factory()->create(['status' => 'active']);
    Colocation::factory()->create(['status' => 'cancelled']);

    expect(Colocation::active()->count())->toBe(2);
});

// ─── Invitation Relationships ──────────────────────────────────────────

test('invitation belongs to colocation', function () {
    $colocation = Colocation::factory()->create();
    $invitation = Invitation::factory()->create(['colocation_id' => $colocation->id]);

    expect($invitation->colocation->id)->toBe($colocation->id);
});

// ─── Category Relationships ────────────────────────────────────────────

test('category belongs to colocation', function () {
    $colocation = Colocation::factory()->create();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    expect($category->colocation->id)->toBe($colocation->id);
});

test('category has many expenses', function () {
    $colocation = Colocation::factory()->create();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);
    $payer = User::factory()->create();

    Expense::factory()->count(3)->create([
        'category_id' => $category->id,
        'colocation_id' => $colocation->id,
        'payer_id' => $payer->id,
    ]);

    expect($category->expenses)->toHaveCount(3);
});

// ─── Expense Relationships ─────────────────────────────────────────────

test('expense belongs to payer', function () {
    $payer = User::factory()->create();
    $colocation = Colocation::factory()->create();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    $expense = Expense::factory()->create([
        'payer_id' => $payer->id,
        'colocation_id' => $colocation->id,
        'category_id' => $category->id,
    ]);

    expect($expense->payer->id)->toBe($payer->id);
});

test('expense belongs to colocation and category', function () {
    $colocation = Colocation::factory()->create();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    $expense = Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'category_id' => $category->id,
    ]);

    expect($expense->colocation->id)->toBe($colocation->id);
    expect($expense->category->id)->toBe($category->id);
});

test('expense casts amount as decimal and date as date', function () {
    $colocation = Colocation::factory()->create();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    $expense = Expense::factory()->create([
        'amount' => 49.99,
        'date' => '2025-06-15',
        'colocation_id' => $colocation->id,
        'category_id' => $category->id,
    ]);

    expect($expense->amount)->toBe('49.99');
    expect($expense->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

// ─── Settlement Relationships ──────────────────────────────────────────

test('settlement belongs to fromUser and toUser', function () {
    $debtor = User::factory()->create();
    $creditor = User::factory()->create();
    $colocation = Colocation::factory()->create();

    $settlement = Settlement::factory()->create([
        'from_user_id' => $debtor->id,
        'to_user_id' => $creditor->id,
        'colocation_id' => $colocation->id,
    ]);

    expect($settlement->fromUser->id)->toBe($debtor->id);
    expect($settlement->toUser->id)->toBe($creditor->id);
});

test('settlement casts is_paid as boolean', function () {
    $settlement = Settlement::factory()->create(['is_paid' => true]);

    expect($settlement->is_paid)->toBeTrue();
});

// ─── User Model (Phase 2 additions) ───────────────────────────────────

test('user has active colocation returns correct result', function () {
    $user = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $user->id]);

    // Not attached yet
    expect($user->hasActiveColocation())->toBeFalse();

    // Attach as active member
    $colocation->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
    // Clear cached relation
    $user->unsetRelation('colocations');

    expect($user->hasActiveColocation())->toBeTrue();
});

test('user colocations returns all memberships', function () {
    $user = User::factory()->create();
    $colocation1 = Colocation::factory()->create(['owner_id' => $user->id]);
    $colocation2 = Colocation::factory()->create(['owner_id' => $user->id, 'status' => 'cancelled']);

    $colocation1->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
    $colocation2->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now(), 'left_at' => now()]);

    expect($user->colocations)->toHaveCount(2);
});

test('user expenses returns expenses paid by user', function () {
    $user = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    Expense::factory()->count(3)->create([
        'payer_id' => $user->id,
        'colocation_id' => $colocation->id,
        'category_id' => $category->id,
    ]);

    expect($user->expenses)->toHaveCount(3);
});

test('user isOwnerOf returns true for owned colocations', function () {
    $user = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $user->id]);
    $otherColocation = Colocation::factory()->create();

    expect($user->isOwnerOf($colocation))->toBeTrue();
    expect($user->isOwnerOf($otherColocation))->toBeFalse();
});
