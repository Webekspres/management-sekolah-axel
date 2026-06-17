<?php

use App\Models\User;

test('user can sign in through the login form', function (): void {
    $user = User::factory()->asAdmin()->create([
        'email' => 'admin-browser@example.com',
    ]);

    visit('/login')
        ->assertSee('Masuk ke akun Anda')
        ->fill('input[type="email"]', 'admin-browser@example.com')
        ->fill('input[type="password"]', 'password')
        ->click('Masuk')
        ->assertPathIs('/admin')
        ->assertNoSmoke();

    test()->assertAuthenticatedAs($user);
});

test('login shows validation errors for invalid credentials', function (): void {
    visit('/login')
        ->fill('input[type="email"]', 'nobody@example.com')
        ->fill('input[type="password"]', 'wrong-password')
        ->click('Masuk')
        ->assertPathIs('/login')
        ->assertNoJavaScriptErrors();

    test()->assertGuest();
});
