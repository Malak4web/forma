<?php

namespace App\Support;

use App\Models\Exercise;
use App\Models\Program;

/**
 * Builds a program day from its weekly exercise pool.
 * Must stay in sync with programDay() in web/src/data.js (the SPA renders the outline with it).
 */
class ProgramDays
{
    /** [work, rest] seconds per week; last value repeats. */
    private const LOAD = [[30, 30], [40, 20], [45, 15], [50, 10], [50, 10]];

    public static function day(Program $program, int $n): array
    {
        $weeks = $program->weeks;
        $w = min((int) ceil($n / 7), $weeks->count()) - 1;
        $week = $weeks[$w];

        if ($n % 7 === 0) {
            $slugs = ['toetouch'];
            [$work, $rest, $rounds, $title] = [40, 20, 2, 'راحة نشيطة'];
        } else {
            $pool = $week->pool;
            $start = (($n - 1) * 2) % count($pool);
            $slugs = array_map(fn ($k) => $pool[($start + $k) % count($pool)], [0, 1, 2]);
            [$work, $rest] = self::LOAD[min($w, count(self::LOAD) - 1)];
            [$rounds, $title] = [3, $week->title];
        }

        $exercises = Exercise::whereIn('slug', $slugs)->get()->keyBy('slug');

        return [
            'n' => $n,
            'week' => $w + 1,
            'rest' => $n % 7 === 0,
            'title' => $title,
            'rounds' => $rounds,
            'items' => collect($slugs)
                ->filter(fn ($s) => $exercises->has($s))
                ->map(fn ($s) => array_merge($exercises[$s]->toPublic(), ['work' => $work, 'rest' => $rest]))
                ->values(),
        ];
    }
}
