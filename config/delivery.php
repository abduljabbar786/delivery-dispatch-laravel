<?php

return [
    'pickup_location' => [
        'lat' => env('PICKUP_LOCATION_LAT'),
        'lng' => env('PICKUP_LOCATION_LNG'),
    ],
    'thresholds' => [
        'delivery_start_distance' => 100, // meters
        'auto_update_time' => 30, // seconds
    ],
];
