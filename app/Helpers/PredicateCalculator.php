<?php

namespace App\Helpers;

class PredicateCalculator
{
    /**
     * Calculate the predicate label for a given numeric score.
     *
     * Returns:
     *   'A (Sangat Baik)' for score >= 86
     *   'B (Baik)'        for score >= 73
     *   'C (Cukup)'       for score >= 60
     *   'D (Kurang)'      for score < 60
     *   '—'               when score is null
     */
    public static function calculate(?float $score): string
    {
        if ($score === null) {
            return '—';
        }

        return match (true) {
            $score >= 86 => 'A (Sangat Baik)',
            $score >= 73 => 'B (Baik)',
            $score >= 60 => 'C (Cukup)',
            default => 'D (Kurang)',
        };
    }
}
