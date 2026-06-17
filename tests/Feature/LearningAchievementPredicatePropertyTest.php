<?php

use App\Helpers\PredicateCalculator;
use App\Models\LearningAchievement;

/**
 * Property 1: Logika Kalkulasi Predikat
 * For any numeric score 0–100, PredicateCalculator::calculate() SHALL return
 * the correct predicate: A for >=86, B for >=73, C for >=60, D for <60.
 * Validates: Requirements 7.2, 7.3, 7.4
 */
it('always returns a valid predicate for any integer score 0-100', function (int $score) {
    $result = PredicateCalculator::calculate((float) $score);

    expect($result)->toBeIn(['A (Sangat Baik)', 'B (Baik)', 'C (Cukup)', 'D (Kurang)']);
})->with(range(0, 100));

it('returns correct predicate boundaries', function (float $score, string $expected) {
    expect(PredicateCalculator::calculate($score))->toBe($expected);
})->with([
    [86.0, 'A (Sangat Baik)'],
    [100.0, 'A (Sangat Baik)'],
    [85.9, 'B (Baik)'],
    [73.0, 'B (Baik)'],
    [72.9, 'C (Cukup)'],
    [60.0, 'C (Cukup)'],
    [59.9, 'D (Kurang)'],
    [0.0, 'D (Kurang)'],
]);

/**
 * Property 2: Nilai Null Menghasilkan Placeholder
 * For any call to PredicateCalculator::calculate(null), the function SHALL
 * return '—' without throwing an exception.
 * Validates: Requirements 7.5
 */
it('returns dash for null score without throwing exception', function () {
    expect(PredicateCalculator::calculate(null))->toBe('—');
});

/**
 * Property 3: Backward Compatibility — Data Lama Tetap Valid
 * For any LearningAchievement created without the new columns (all null),
 * the model SHALL be saved and retrieved from the database without error,
 * with all new columns being null.
 * Validates: Requirements 15.1, 15.2
 */
it('can save and retrieve learning achievement with all new columns null', function () {
    $record = LearningAchievement::factory()->create([
        'material_coverage_status' => null,
        'daily_assessment_predicate' => null,
        'midterm_assessment_predicate' => null,
        'final_assessment_predicate' => null,
        'achievement_status' => null,
    ]);

    $fresh = LearningAchievement::find($record->id);

    expect($fresh)->not->toBeNull()
        ->and($fresh->material_coverage_status)->toBeNull()
        ->and($fresh->daily_assessment_predicate)->toBeNull()
        ->and($fresh->midterm_assessment_predicate)->toBeNull()
        ->and($fresh->final_assessment_predicate)->toBeNull()
        ->and($fresh->achievement_status)->toBeNull();
});
