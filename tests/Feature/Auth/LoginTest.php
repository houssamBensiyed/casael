<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Phase 1 Tests: Login & Authentication
|--------------------------------------------------------------------------
*/

test('login page is accessible', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});

test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('user cannot login with wrong password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('banned user cannot login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_banned' => true,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    // The user authenticates but the EnsureNotBanned middleware
    // catches them on the redirect and logs them out
    $response = $this->get('/dashboard');
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
});

test('guest cannot access dashboard', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect(route('login'));
});

test('user can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
