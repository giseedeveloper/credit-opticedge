<?php

test('home route shows the public landing page for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('LOGIN', false);
});
