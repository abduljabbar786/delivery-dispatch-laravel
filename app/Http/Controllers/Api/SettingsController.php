<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get restaurant settings (optionally for a specific branch)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id');

        $settings = [
            'restaurant_name' => RestaurantSetting::get('restaurant_name', 'Restaurant', $branchId),
            'opening_time' => RestaurantSetting::get('opening_time', '09:00', $branchId),
            'closing_time' => RestaurantSetting::get('closing_time', '22:00', $branchId),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
            'branch_id' => $branchId,
        ]);
    }

    /**
     * Update restaurant settings (optionally for a specific branch)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'restaurant_name' => 'required|string|max:255',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $branchId = $validated['branch_id'] ?? null;

        // Update each setting (with optional branch_id)
        RestaurantSetting::set('restaurant_name', $validated['restaurant_name'], 'Restaurant name', $branchId);
        RestaurantSetting::set('opening_time', $validated['opening_time'], 'Restaurant opening time (24-hour format)', $branchId);
        RestaurantSetting::set('closing_time', $validated['closing_time'], 'Restaurant closing time (24-hour format)', $branchId);

        // Return updated settings
        $settings = [
            'restaurant_name' => RestaurantSetting::get('restaurant_name', null, $branchId),
            'opening_time' => RestaurantSetting::get('opening_time', null, $branchId),
            'closing_time' => RestaurantSetting::get('closing_time', null, $branchId),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $settings,
            'branch_id' => $branchId,
        ]);
    }
}
