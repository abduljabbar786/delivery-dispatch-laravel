<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'branch_id',
        'key',
        'value',
        'description',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get a setting value by key (with optional branch)
     *
     * @param string $key
     * @param mixed $default
     * @param int|null $branchId
     * @return mixed
     */
    public static function get(string $key, $default = null, ?int $branchId = null)
    {
        $cacheKey = "restaurant_setting:{$key}:" . ($branchId ?? 'global');

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($key, $branchId, $default) {
            $query = static::where('key', $key);

            if ($branchId) {
                // Try to get branch-specific setting first
                $setting = $query->where('branch_id', $branchId)->first();

                // If not found, fallback to global setting
                if (!$setting) {
                    $setting = static::where('key', $key)->whereNull('branch_id')->first();
                }
            } else {
                // Get global setting
                $setting = $query->whereNull('branch_id')->first();
            }

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value (with optional branch)
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @param int|null $branchId
     * @return static
     */
    public static function set(string $key, $value, ?string $description = null, ?int $branchId = null)
    {
        $setting = static::updateOrCreate(
            [
                'key' => $key,
                'branch_id' => $branchId,
            ],
            [
                'value' => $value,
                'description' => $description,
            ]
        );

        // Clear cache for this setting
        $cacheKey = "restaurant_setting:{$key}:" . ($branchId ?? 'global');
        Cache::forget($cacheKey);

        // Also clear global cache if setting a branch-specific value
        if ($branchId) {
            Cache::forget("restaurant_setting:{$key}:global");
        }

        return $setting;
    }
}
