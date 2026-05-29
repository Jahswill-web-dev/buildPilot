<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('guest auth pages render as inertia pages', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login'));

    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Register'));
});

test('guests are redirected from the idea dashboard', function () {
    $this->get('/ideas')
        ->assertRedirect('/login');
});

test('authenticated users can view the idea dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/ideas')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Ideas/Index'));
});

test('guests can create an account and are signed in', function () {
    $response = $this->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ADA@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/ideas');
    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);
});

test('registration validates password confirmation', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertRedirect('/register');
    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('users can sign in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'ada@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'ADA@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/ideas');
    $this->assertAuthenticatedAs($user);
});

test('users cannot sign in with invalid credentials', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'ada@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('authenticated users can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});
