<?php

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────

function createAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

// ─── Dashboard ─────────────────────────────────────────────────────────

test('admin can access dashboard', function () {
    $admin = createAdmin();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Administration');
});

test('regular user cannot access admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('guest cannot access admin dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('dashboard shows correct stats', function () {
    $admin = createAdmin();

    // Create some data
    User::factory()->count(3)->create();
    User::factory()->create(['is_banned' => true]);
    Colocation::factory()->count(2)->create();
    Colocation::factory()->create(['status' => 'cancelled']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('5');  // total users: admin + 3 + 1 banned = 5
});

// ─── Ban ────────────────────────────────────────────────────────────────

test('admin can ban a user', function () {
    $admin = createAdmin();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.ban', $user))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success');

    expect($user->fresh()->is_banned)->toBeTrue();
});

test('admin cannot ban themselves', function () {
    $admin = createAdmin();

    $this->actingAs($admin)
        ->patch(route('admin.ban', $admin))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error');

    expect($admin->fresh()->is_banned)->toBeFalse();
});

test('admin cannot ban another admin', function () {
    $admin1 = createAdmin();
    $admin2 = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin1)
        ->patch(route('admin.ban', $admin2))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error');

    expect($admin2->fresh()->is_banned)->toBeFalse();
});

test('regular user cannot ban anyone', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('admin.ban', $target))
        ->assertForbidden();
});

// ─── Unban ──────────────────────────────────────────────────────────────

test('admin can unban a user', function () {
    $admin = createAdmin();
    $banned = User::factory()->create(['is_banned' => true]);

    $this->actingAs($admin)
        ->patch(route('admin.unban', $banned))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success');

    expect($banned->fresh()->is_banned)->toBeFalse();
});

test('regular user cannot unban anyone', function () {
    $user = User::factory()->create();
    $banned = User::factory()->create(['is_banned' => true]);

    $this->actingAs($user)
        ->patch(route('admin.unban', $banned))
        ->assertForbidden();

    expect($banned->fresh()->is_banned)->toBeTrue();
});
