<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MapController extends Controller
{
    public function riderPositions(Request $request)
    {
        $cacheKey = 'riders:positions:latest';

        $riders = Cache::remember($cacheKey, now()->addSeconds(30), function () {
            return Rider::query()->whereNotNull('latest_lat')
                ->whereNotNull('latest_lng')
                ->select([
                    'id',
                    'name',
                    'status',
                    'latest_lat as lat',
                    'latest_lng as lng',
                    'battery',
                    'last_seen_at'
                ])
                ->get();
        });

        return response()->json($riders);
    }
}
