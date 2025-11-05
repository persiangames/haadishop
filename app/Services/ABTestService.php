<?php

namespace App\Services;

use App\Models\ABTest;
use App\Models\ABTestAssignment;
use App\Models\ABTestMetric;
use Illuminate\Support\Facades\DB;

class ABTestService
{
    /**
     * ایجاد تست A/B جدید
     */
    public function createTest($key, $variants, $description = null)
    {
        $test = ABTest::create([
            'key' => $key,
            'variants' => $variants,
            'description' => $description,
            'status' => 'active',
        ]);

        return $test;
    }

    /**
     * اختصاص variant به کاربر
     */
    public function assignVariant($testKey, $userId = null, $sessionId = null)
    {
        $test = ABTest::where('key', $testKey)
            ->where('status', 'active')
            ->first();

        if (!$test) {
            return null;
        }

        // بررسی اینکه آیا کاربر قبلاً variant اختصاص داده شده است
        $existing = ABTestAssignment::where('ab_test_id', $test->id)
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
                if ($sessionId) {
                    $q->orWhere('session_id', $sessionId);
                }
            })
            ->first();

        if ($existing) {
            return $existing->variant;
        }

        // اختصاص variant تصادفی
        $variants = $test->variants;
        $variant = $variants[array_rand($variants)];

        ABTestAssignment::create([
            'ab_test_id' => $test->id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'variant' => $variant,
            'assigned_at' => now(),
        ]);

        return $variant;
    }

    /**
     * ثبت متریک
     */
    public function trackMetric($testKey, $eventKey, $value = 1, $userId = null, $sessionId = null)
    {
        $test = ABTest::where('key', $testKey)->first();

        if (!$test) {
            return false;
        }

        $assignment = ABTestAssignment::where('ab_test_id', $test->id)
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
                if ($sessionId) {
                    $q->orWhere('session_id', $sessionId);
                }
            })
            ->first();

        if (!$assignment) {
            return false;
        }

        ABTestMetric::create([
            'ab_test_id' => $test->id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'event_key' => $eventKey,
            'value' => $value,
            'occurred_at' => now(),
        ]);

        return true;
    }

    /**
     * دریافت نتایج تست A/B
     */
    public function getTestResults($testId)
    {
        $test = ABTest::findOrFail($testId);

        $results = [];

        foreach ($test->variants as $variant) {
            $assignments = ABTestAssignment::where('ab_test_id', $test->id)
                ->where('variant', $variant)
                ->count();

            $userIds = ABTestAssignment::where('ab_test_id', $test->id)
                ->where('variant', $variant)
                ->whereNotNull('user_id')
                ->pluck('user_id');
            
            $sessionIds = ABTestAssignment::where('ab_test_id', $test->id)
                ->where('variant', $variant)
                ->whereNotNull('session_id')
                ->pluck('session_id');

            $metrics = ABTestMetric::where('ab_test_id', $test->id)
                ->where(function ($q) use ($userIds, $sessionIds) {
                    $q->whereIn('user_id', $userIds)
                      ->orWhereIn('session_id', $sessionIds);
                })
                ->select('event_key', DB::raw('SUM(value) as total'))
                ->groupBy('event_key')
                ->get();

            $results[$variant] = [
                'assignments' => $assignments,
                'metrics' => $metrics->pluck('total', 'event_key'),
            ];
        }

        return $results;
    }
}

