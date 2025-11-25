<?php

namespace App\Helpers;

use App\Models\RestaurantSetting;
use Carbon\Carbon;

class RestaurantHelper
{
    /**
     * Get the restaurant opening time
     *
     * @return string
     */
    public static function getOpeningTime(): string
    {
        return RestaurantSetting::get('opening_time', '16:00');
    }

    /**
     * Get the restaurant closing time
     *
     * @return string
     */
    public static function getClosingTime(): string
    {
        return RestaurantSetting::get('closing_time', '04:00');
    }

    /**
     * Check if a given timestamp is within restaurant operating hours
     * Handles overnight hours (e.g., 4 PM to 4 AM)
     *
     * @param Carbon $timestamp
     * @return bool
     */
    public static function isWithinOperatingHours(Carbon $timestamp): bool
    {
        $openingTime = self::getOpeningTime();
        $closingTime = self::getClosingTime();

        $openingHour = (int) substr($openingTime, 0, 2);
        $closingHour = (int) substr($closingTime, 0, 2);

        $hour = $timestamp->hour;

        // Handle overnight hours (e.g., 16:00 to 04:00)
        if ($closingHour < $openingHour) {
            // Operating hours span midnight
            return $hour >= $openingHour || $hour < $closingHour;
        }

        // Normal hours (e.g., 08:00 to 22:00)
        return $hour >= $openingHour && $hour < $closingHour;
    }

    /**
     * Get the start of the current restaurant day
     * For hours spanning midnight, the "day" starts at opening time
     *
     * @return Carbon
     */
    public static function getCurrentDayStart(): Carbon
    {
        $openingTime = self::getOpeningTime();
        $closingTime = self::getClosingTime();

        $openingHour = (int) substr($openingTime, 0, 2);
        $closingHour = (int) substr($closingTime, 0, 2);

        $now = Carbon::now();
        $currentHour = $now->hour;

        // If closing time is before opening time (overnight hours)
        if ($closingHour < $openingHour) {
            // If current time is before closing (e.g., 2 AM when closing is 4 AM)
            // The day started yesterday at opening time
            if ($currentHour < $closingHour) {
                return Carbon::yesterday()->setTimeFromTimeString($openingTime);
            }
        }

        // Otherwise, day starts today at opening time
        return Carbon::today()->setTimeFromTimeString($openingTime);
    }

    /**
     * Get the end of the current restaurant day
     *
     * @return Carbon
     */
    public static function getCurrentDayEnd(): Carbon
    {
        $openingTime = self::getOpeningTime();
        $closingTime = self::getClosingTime();

        $openingHour = (int) substr($openingTime, 0, 2);
        $closingHour = (int) substr($closingTime, 0, 2);

        $now = Carbon::now();
        $currentHour = $now->hour;

        // If closing time is before opening time (overnight hours)
        if ($closingHour < $openingHour) {
            // If current time is before closing (e.g., 2 AM when closing is 4 AM)
            // The day ends today at closing time
            if ($currentHour < $closingHour) {
                return Carbon::today()->setTimeFromTimeString($closingTime);
            }
            // Otherwise, day ends tomorrow at closing time
            return Carbon::tomorrow()->setTimeFromTimeString($closingTime);
        }

        // Normal hours: day ends today at closing time
        return Carbon::today()->setTimeFromTimeString($closingTime);
    }

    /**
     * Get orders within current restaurant day
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function filterCurrentDayOrders($query)
    {
        $dayStart = self::getCurrentDayStart();
        $dayEnd = self::getCurrentDayEnd();

        return $query->whereBetween('created_at', [$dayStart, $dayEnd]);
    }
}
