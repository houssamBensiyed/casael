<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Phase 1 Tests: User Model Helper Methods
|--------------------------------------------------------------------------
*/

test('user has default role of user', function () {
    $user = User::factory()->create();
    expect($user->role)->toBe('user');
});

test('user has default reputation of 0', function () {
    $user = User::factory()->create();
    expect($user->reputation)->toBe(0);
});

test('user is not banned by default', function () {
    $user = User::factory()->create();
    expect($user->is_banned)->toBeFalse();
    expect($user->isBanned())->toBeFalse();
});

test('isAdmin returns true for admin users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    expect($admin->isAdmin())->toBeTrue();
});

test('isAdmin returns false for regular users', function () {
    $user = User::factory()->create(['role' => 'user']);
    expect($user->isAdmin())->toBeFalse();
});

test('isBanned returns true when user is banned', function () {
    $user = User::factory()->create(['is_banned' => true]);
    expect($user->isBanned())->toBeTrue();
});

test('isBanned returns false when user is not banned', function () {
    $user = User::factory()->create(['is_banned' => false]);
    expect($user->isBanned())->toBeFalse();
});

test('reputation can be incremented', function () {
    $user = User::factory()->create(['reputation' => 0]);
    $user->update(['reputation' => $user->reputation + 1]);
    expect($user->fresh()->reputation)->toBe(1);
});

test('reputation can be decremented', function () {
    $user = User::factory()->create(['reputation' => 0]);
    $user->update(['reputation' => $user->reputation - 1]);
    expect($user->fresh()->reputation)->toBe(-1);
});
