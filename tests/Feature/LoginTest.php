<?php

declare(strict_types=1);

use App\Models\User;

it('sends guests to the login page', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/login')->assertOk();
});

it('logs in with the right password and out again', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});

it('rejects a wrong password', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->assertGuest();
});
