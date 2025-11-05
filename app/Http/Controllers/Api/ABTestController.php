<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ABTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ABTestController extends Controller
{
    protected ABTestService $abTestService;

    public function __construct(ABTestService $abTestService)
    {
        $this->abTestService = $abTestService;
    }

    /**
     * دریافت variant برای کاربر
     */
    public function getVariant(Request $request, $testKey)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);

        $variant = $this->abTestService->assignVariant(
            $testKey,
            $user?->id,
            $sessionId
        );

        return response()->json([
            'variant' => $variant,
            'test_key' => $testKey,
        ], 200);
    }

    /**
     * ثبت متریک
     */
    public function track(Request $request, $testKey)
    {
        $request->validate([
            'event_key' => ['required', 'string'],
            'value' => ['sometimes', 'numeric'],
        ]);

        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);

        $this->abTestService->trackMetric(
            $testKey,
            $request->event_key,
            $request->get('value', 1),
            $user?->id,
            $sessionId
        );

        return response()->json([
            'message' => 'Metric tracked successfully.',
        ], 200);
    }
}

