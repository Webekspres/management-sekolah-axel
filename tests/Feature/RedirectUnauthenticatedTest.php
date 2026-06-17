<?php

test('unauthenticated users are redirected to central login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect(route('filament.auth.auth.login'));
});
