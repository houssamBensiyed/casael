<?php

use App\Models\Category;
use App\Models\Colocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────

function setupColocationForCategory(): array
{
    $owner = User::factory()->create();
    $colocation = Colocation::factory()->create(['owner_id' => $owner->id]);
    $colocation->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

    $member = User::factory()->create();
    $colocation->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    return [$owner, $member, $colocation];
}

// ─── Store ─────────────────────────────────────────────────────────────

test('active member can create category', function () {
    [$owner, $member, $colocation] = setupColocationForCategory();

    $this->actingAs($member)
        ->post(route('categories.store', $colocation), ['name' => 'Courses'])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect(Category::where('name', 'Courses')->count())->toBe(1);
});

test('non-member cannot create category', function () {
    [$owner, $member, $colocation] = setupColocationForCategory();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('categories.store', $colocation), ['name' => 'Hack'])
        ->assertForbidden();
});

test('cannot create category on cancelled colocation', function () {
    [$owner, $member, $colocation] = setupColocationForCategory();
    $colocation->update(['status' => 'cancelled']);

    $this->actingAs($owner)
        ->post(route('categories.store', $colocation), ['name' => 'Test'])
        ->assertForbidden();
});

// ─── Update ────────────────────────────────────────────────────────────

test('active member can update category', function () {
    [$owner, $member, $colocation] = setupColocationForCategory();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    $this->actingAs($member)
        ->put(route('categories.update', $category), ['name' => 'Loyer'])
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect($category->fresh()->name)->toBe('Loyer');
});

test('non-member cannot update category', function () {
    [$owner, $member, $colocation] = setupColocationForCategory();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->put(route('categories.update', $category), ['name' => 'Hack'])
        ->assertForbidden();
});

// ─── Destroy ───────────────────────────────────────────────────────────

test('owner can delete category', function () {
    [$owner, $member, $colocation] = setupColocationForCategory();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    $this->actingAs($owner)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('colocations.show', $colocation))
        ->assertSessionHas('success');

    expect(Category::count())->toBe(0);
});

test('member cannot delete category', function () {
    [$owner, $member, $colocation] = setupColocationForCategory();
    $category = Category::factory()->create(['colocation_id' => $colocation->id]);

    $this->actingAs($member)
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();

    expect(Category::count())->toBe(1);
});
