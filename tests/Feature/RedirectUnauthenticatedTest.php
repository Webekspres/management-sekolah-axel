<?php

test('unauthenticated users are redirected to central login', function () {
    $response = $this->get('/admin/academic');

    $response->assertRedirect(route('filament.auth.auth.login'));
});
