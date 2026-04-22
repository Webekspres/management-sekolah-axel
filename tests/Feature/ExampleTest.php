<?php

test('redirects guests to login page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
