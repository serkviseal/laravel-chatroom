<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register', function () {
    $this->post('/register', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/home');

    $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    $this->assertAuthenticated();
});

test('registration requires a unique email', function () {
    User::factory()->create(['email' => 'alice@example.com']);

    $this->post('/register', [
        'name' => 'Alice2',
        'email' => 'alice@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');
});
