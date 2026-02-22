<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('profile.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('profile.index'));
    $response->assertOk();
});
