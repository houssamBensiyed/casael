<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Phase 1 Tests: Registration & Auto-Admin Promotion
|--------------------------------------------------------------------------
*/

test('registration page is accessible', function () {
    $response = $this->get('/register');
    $response->assertStatus(200);
});

test('a new user can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@casael.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@casael.test')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Test User');
});

test('first registered user is auto-promoted to admin', function () {
    // Ensure no users exist
    expect(User::count())->toBe(0);

    $this->post('/register', [
        'name' => 'First User',
        'email' => 'first@casael.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'first@casael.test')->first();
    expect($user->role)->toBe('admin');
    expect($user->isAdmin())->toBeTrue();
});

test('second registered user is NOT promoted to admin', function () {
    // Create the first user (becomes admin)
    User::factory()->create(['email' => 'first@casael.test']);

    // Register the second user
    $this->post('/register', [
        'name' => 'Second User',
        'email' => 'second@casael.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'second@casael.test')->first();
    expect($user->role)->toBe('user');
    expect($user->isAdmin())->toBeFalse();
});

test('registration fails with invalid data', function () {
    $response = $this->post('/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'taken@casael.test']);

    $response = $this->post('/register', [
        'name' => 'Another User',
        'email' => 'taken@casael.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});
