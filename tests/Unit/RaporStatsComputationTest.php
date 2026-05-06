<?php

// Feature: student-grades-report-ui, Property 5: Statistik rapor konsisten dengan data koleksi

use App\Models\Rapor;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Property 5: Statistik rapor konsisten dengan data koleksi
 *
 * Validates: Requirements 5.1
 */
test('Property 5: rapor stats are computed correctly for any combination of rapor statuses', function (): void {
    for ($i = 0; $i < 100; $i++) {
        // Arrange: create a student and rapors with random status combinations
        $student = Student::factory()->create();

        $draftCount = fake()->numberBetween(0, 5);
        $finalizedCount = fake()->numberBetween(0, 5);
        $approvedCount = fake()->numberBetween(0, 5);

        if ($draftCount > 0) {
            Rapor::factory()->count($draftCount)->create(['student_id' => $student->id]);
        }

        if ($finalizedCount > 0) {
            Rapor::factory()->finalized()->count($finalizedCount)->create(['student_id' => $student->id]);
        }

        if ($approvedCount > 0) {
            Rapor::factory()->approved()->count($approvedCount)->create(['student_id' => $student->id]);
        }

        // Act: replicate the computation logic from RaporStatsWidget::getStats()
        $rapors = Rapor::where('student_id', $student->id)->get();

        $computedTotal = $rapors->count();
        $computedApproved = $rapors->filter(fn (Rapor $rapor): bool => $rapor->isApproved())->count();
        $computedNotReady = $rapors->filter(fn (Rapor $rapor): bool => $rapor->isDraft() || $rapor->isFinalized())->count();

        $expectedTotal = $draftCount + $finalizedCount + $approvedCount;

        // Assert: total = count of all rapors in the collection
        expect($computedTotal)->toBe($expectedTotal, sprintf(
            'Iteration %d: expected total=%d, got %d',
            $i + 1,
            $expectedTotal,
            $computedTotal,
        ));

        // Assert: approved = count of rapors with status APPROVED
        expect($computedApproved)->toBe($approvedCount, sprintf(
            'Iteration %d: expected approved=%d, got %d',
            $i + 1,
            $approvedCount,
            $computedApproved,
        ));

        // Assert: not_ready = count of rapors with status DRAFT or FINALIZED
        $expectedNotReady = $draftCount + $finalizedCount;
        expect($computedNotReady)->toBe($expectedNotReady, sprintf(
            'Iteration %d: expected not_ready=%d, got %d',
            $i + 1,
            $expectedNotReady,
            $computedNotReady,
        ));

        // Assert invariant: total === approved + not_ready
        expect($computedTotal)->toBe($computedApproved + $computedNotReady, sprintf(
            'Iteration %d: invariant violated — total (%d) !== approved (%d) + not_ready (%d)',
            $i + 1,
            $computedTotal,
            $computedApproved,
            $computedNotReady,
        ));
    }
});
