<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Phase 1 Tests: EnsureNotBanned Middleware
|--------------------------------------------------------------------------
*/

test('non-banned user can access authenticated routes', function () {
    $user = User::factory()->create(['is_banned' => false]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
});

test('banned user is logged out and redirected to login', function () {
    $user = User::factory()->create(['is_banned' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    // Should be redirected to login
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('banned user sees error message on redirect', function () {
    $user = User::factory()->create(['is_banned' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('login'));
    // Follow the redirect to check for errors
    $response->assertSessionHasErrors(['email']);
});

test('banned user cannot access profile page', function () {
    $user = User::factory()->create(['is_banned' => true]);

    $response = $this->actingAs($user)->get('/profile');
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('banned user session is invalidated', function () {
    $user = User::factory()->create(['is_banned' => false]);

    // Login first
    $this->actingAs($user);
    $this->assertAuthenticated();

    // Now ban the user
    $user->update(['is_banned' => true]);

    // Next request should log them out
    $response = $this->get('/dashboard');
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('login page is still accessible for banned user (guest route)', function () {
    // Login page should always be accessible even for banned users
    $response = $this->get('/login');
    $response->assertStatus(200);
});
