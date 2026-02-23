<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Phase 1 Tests: AdminMiddleware
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    // Register a test route protected by admin middleware
    Route::middleware(['auth', 'admin'])->get('/admin/test-route', function () {
        return response('Admin area content', 200);
    })->name('admin.test');
});

test('admin user can access admin-protected routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/test-route');
    $response->assertStatus(200);
    $response->assertSee('Admin area content');
});

test('regular user gets 403 on admin-protected routes', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->get('/admin/test-route');
    $response->assertStatus(403);
});

test('guest user is redirected to login on admin-protected routes', function () {
    $response = $this->get('/admin/test-route');
    $response->assertRedirect(route('login'));
});

test('admin who is also banned gets blocked by banned middleware first', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'is_banned' => true,
    ]);

    // Access a real route that goes through the full web middleware stack
    $response = $this->actingAs($user)->get('/dashboard');
    // Banned middleware runs globally, so user gets logged out
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
