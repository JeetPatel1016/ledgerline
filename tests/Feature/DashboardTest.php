<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('renders the dashboard through Inertia', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('appName', config('app.name')));
});
