<?php

use Filament\Facades\Filament;

test('create and edit redirect default to index on all resource panels', function () {
    $panelIds = ['admin', 'guru', 'kepsek', 'student'];

    foreach ($panelIds as $panelId) {
        Filament::setCurrentPanel(Filament::getPanel($panelId));

        expect(Filament::getResourceCreatePageRedirect())->toBe('index')
            ->and(Filament::getResourceEditPageRedirect())->toBe('index');
    }
});
