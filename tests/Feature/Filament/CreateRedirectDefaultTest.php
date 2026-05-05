<?php

use Filament\Facades\Filament;

test('create redirect defaults to index on all panels', function () {
    $panelIds = ['admin', 'guru', 'kepsek', 'student'];

    foreach ($panelIds as $panelId) {
        Filament::setCurrentPanel(Filament::getPanel($panelId));

        expect(Filament::getResourceCreatePageRedirect())->toBe('index');
    }
});
