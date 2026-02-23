<?php

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────

function setupColocationForExpense(): array
{
    $owner = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);
    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

    $member = User::factory()->create();
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    return [$owner, $member, $colocation, $category];
}

// ─── Store ─────────────────────────────────────────────────────────────

test('active member can create expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $this->actingAs($member)
        ->post(route('expenses.store', $colocation), [
            'title' => 'Courses Lidl',
            'amount' => 42.50,
            'date' => '2025-06-15',
            'category_id' => $category->id,
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    $expense = Expense::first();
    expect($expense->title)->toBe('Courses Lidl');
    expect((float) $expense->amount)->toBe(42.50);
    expect($expense->payer_id)->toBe($member->id);
    expect($expense->colocation_id)->toBe($colocation->id);
    expect($expense->category_id)->toBe($category->id);
});

test('expense can be created without category', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $this->actingAs($member)
        ->post(route('expenses.store', $colocation), [
            'title' => 'Divers',
            'amount' => 10.00,
            'date' => '2025-06-15',
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect(Expense::first()->category_id)->toBeNull();
});

test('non-member cannot create expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('expenses.store', $colocation), [
            'title' => 'Hack',
            'amount' => 100,
            'date' => '2025-06-15',
        ])
        ->assertForbidden();
});

test('cannot create expense on cancelled colocation', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();
    $colocation->update(['status' => 'cancelled']);

    $this->actingAs($owner)
        ->post(route('expenses.store', $colocation), [
            'title' => 'Test',
            'amount' => 10,
            'date' => '2025-06-15',
        ])
        ->assertForbidden();
});

test('cannot use category from another colocation', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $otherCategory = Category::factory()->create(); // belongs to a different colocation

    $this->actingAs($member)
        ->post(route('expenses.store', $colocation), [
            'title' => 'Cross-coloc',
            'amount' => 10,
            'date' => '2025-06-15',
            'category_id' => $otherCategory->id,
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('error');

    expect(Expense::count())->toBe(0);
});

test('validation rejects invalid amount', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $this->actingAs($member)
        ->post(route('expenses.store', $colocation), [
            'title' => 'Bad',
            'amount' => 0,
            'date' => '2025-06-15',
        ])
        ->assertSessionHasErrors('amount');
});

// ─── Update ────────────────────────────────────────────────────────────

test('payer can update their expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $expense = Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($member)
        ->put(route('expenses.update', $expense), [
            'title' => 'Updated Title',
            'amount' => 99.99,
            'date' => '2025-07-01',
            'category_id' => $category->id,
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    $expense->refresh();
    expect($expense->title)->toBe('Updated Title');
    expect((float) $expense->amount)->toBe(99.99);
});

test('owner can update any expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $expense = Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($owner)
        ->put(route('expenses.update', $expense), [
            'title' => 'Owner Edit',
            'amount' => 50,
            'date' => '2025-07-01',
        ])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect($expense->fresh()->title)->toBe('Owner Edit');
});

test('other member cannot update expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $otherMember = User::factory()->create();
    $colocation->members()->attach($otherMember->id, ['role' => 'member', 'joined_at' => now()]);

    $expense = Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($otherMember)
        ->put(route('expenses.update', $expense), [
            'title' => 'Hijack',
            'amount' => 1,
            'date' => '2025-07-01',
        ])
        ->assertForbidden();
});

// ─── Destroy ───────────────────────────────────────────────────────────

test('payer can delete their expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $expense = Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($member)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect(Expense::count())->toBe(0);
});

test('owner can delete any expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $expense = Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($owner)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect(Expense::count())->toBe(0);
});

test('other member cannot delete expense', function () {
    [$owner, $member, $colocation, $category] = setupColocationForExpense();

    $otherMember = User::factory()->create();
    $colocation->members()->attach($otherMember->id, ['role' => 'member', 'joined_at' => now()]);

    $expense = Expense::factory()->create([
        'colocation_id' => $colocation->id,
        'payer_id' => $member->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($otherMember)
        ->delete(route('expenses.destroy', $expense))
        ->assertForbidden();

    expect(Expense::count())->toBe(1);
});
