<?php

use App\Support\RichText;

test('rich text formatter converts html to readable plain text', function () {
    $html = '<p>Perbaiki <strong>indikator</strong> pembelajaran.</p><ul><li>Lengkapi asesmen</li><li>Tambahkan rubrik</li></ul>';

    $text = RichText::toPlainText($html);

    expect($text)->toContain('Perbaiki indikator pembelajaran.')
        ->and($text)->toContain('• Lengkapi asesmen')
        ->and($text)->toContain('• Tambahkan rubrik');
});

test('rich text formatter returns fallback for empty value', function () {
    expect(RichText::display(null))->toBe('-')
        ->and(RichText::display(''))->toBe('-');
});
