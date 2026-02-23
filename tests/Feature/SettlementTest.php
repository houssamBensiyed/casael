<?php

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Settlement;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────

function setupColocationForSettlement(): array
{
    $owner = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);
    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

    $member = User::factory()->create();
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    return [$owner, $member, $colocation];
}

// ─── BalanceService Unit Tests ─────────────────────────────────────────

test('balance service returns empty for no members', function () {
    $colocation = Colocation::factory()->create();
    $service = new BalanceService();

    expect($service->calculateBalances($colocation))->toBe([]);
});

test('balance service calculates equal expenses correctly', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $owner->id,
        'amount' => 100,
        'date' => now(),
    ]);
    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'amount' => 100,
        'date' => now(),
    ]);

    $service = new BalanceService();
    $balances = $service->calculateBalances($colocation);

    expect($balances[$owner->id]['paid'])->toBe(100.0);
    expect($balances[$member->id]['paid'])->toBe(100.0);
    expect($balances[$owner->id]['share'])->toBe(100.0);
    expect($balances[$owner->id]['balance'])->toBe(0.0);
});

test('balance service calculates unequal expenses correctly', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $owner->id,
        'amount' => 200,
        'date' => now(),
    ]);

    $service = new BalanceService();
    $balances = $service->calculateBalances($colocation);

    // Total 200, 2 members → fair share 100 each
    expect($balances[$owner->id]['balance'])->toBe(100.0);   // paid 200, share 100 → +100
    expect($balances[$member->id]['balance'])->toBe(-100.0);  // paid 0, share 100 → -100
});

test('balance service generates correct settlements', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $owner->id,
        'amount' => 200,
        'date' => now(),
    ]);

    $service = new BalanceService();
    $settlements = $service->generateSettlements($colocation);

    expect($settlements)->toHaveCount(1);
    expect($settlements[0]['from'])->toBe($member->id);
    expect($settlements[0]['to'])->toBe($owner->id);
    expect($settlements[0]['amount'])->toBe(100.0);
});

test('balance service handles 3 members correctly', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    $member2 = User::factory()->create();
    $colocation->members()->attach($member2->id, ['role' => 'member', 'joined_at' => now()]);

    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $owner->id,
        'amount' => 300,
        'date' => now(),
    ]);

    $service = new BalanceService();
    $balances = $service->calculateBalances($colocation);

    // Total 300, 3 members → fair share 100 each
    expect($balances[$owner->id]['balance'])->toBe(200.0);
    expect($balances[$member->id]['balance'])->toBe(-100.0);
    expect($balances[$member2->id]['balance'])->toBe(-100.0);

    $settlements = $service->generateSettlements($colocation);
    expect($settlements)->toHaveCount(2);

    // Total owed to owner should be 200
    $totalToOwner = collect($settlements)->sum('amount');
    expect($totalToOwner)->toBe(200.0);
});

test('balance service returns no settlements when balanced', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $owner->id,
        'amount' => 50,
        'date' => now(),
    ]);
    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'amount' => 50,
        'date' => now(),
    ]);

    $service = new BalanceService();
    $settlements = $service->generateSettlements($colocation);
    expect($settlements)->toHaveCount(0);
});

// ─── Settlement Controller Tests ───────────────────────────────────────

test('active member can view settlements page', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    $this->actingAs($member)
        ->get(route('settlements.index', $colocation))
        ->assertOk();
});

test('non-member cannot view settlements page', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('settlements.index', $colocation))
        ->assertForbidden();
});

test('owner can generate settlements', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $owner->id,
        'amount' => 200,
        'date' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('settlements.generate', $colocation))
        ->assertRedirect(route('settlements.index', $colocation))
        ->assertSessionHas('success');

    expect(Settlement::count())->toBe(1);
    $settlement = Settlement::first();
    expect($settlement->from_user_id)->toBe($member->id);
    expect($settlement->to_user_id)->toBe($owner->id);
    expect((float) $settlement->amount)->toBe(100.0);
});

test('non-owner cannot generate settlements', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    $this->actingAs($member)
        ->post(route('settlements.generate', $colocation))
        ->assertForbidden();
});

test('generate replaces old unpaid settlements', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    // Create an old unpaid settlement
    Settlement::factory()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
        'amount' => 999,
        'is_paid' => false,
    ]);

    // Create a paid settlement (should survive)
    Settlement::factory()->paid()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
        'amount' => 50,
    ]);

    Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $owner->id,
        'amount' => 100,
        'date' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('settlements.generate', $colocation));

    // Old unpaid (999) replaced by new (50), paid (50) kept
    expect(Settlement::where('is_paid', false)->count())->toBe(1);
    expect(Settlement::where('is_paid', true)->count())->toBe(1);
    expect((float) Settlement::where('is_paid', false)->first()->amount)->toBe(50.0);
});

test('creditor can mark settlement as paid', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    $settlement = Settlement::factory()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
    ]);

    $this->actingAs($owner) // owner is creditor (to_user)
        ->patch(route('settlements.markPaid', $settlement))
        ->assertRedirect(route('settlements.index', $colocation))
        ->assertSessionHas('success');

    expect($settlement->fresh()->is_paid)->toBeTrue();
});

test('debtor cannot mark settlement as paid', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    $settlement = Settlement::factory()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
    ]);

    $this->actingAs($member) // member is debtor (from_user)
        ->patch(route('settlements.markPaid', $settlement))
        ->assertForbidden();

    expect($settlement->fresh()->is_paid)->toBeFalse();
});

test('already paid settlement cannot be marked again', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();

    $settlement = Settlement::factory()->paid()->create([
        'colocation_id' => $colocation->id,
        'from_user_id' => $member->id,
        'to_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->patch(route('settlements.markPaid', $settlement))
        ->assertForbidden();
});

test('cannot generate settlements on cancelled colocation', function () {
    [$owner, $member, $colocation] = setupColocationForSettlement();
    $colocation->update(['status' => 'cancelled']);

    $this->actingAs($owner)
        ->post(route('settlements.generate', $colocation))
        ->assertForbidden();
});
