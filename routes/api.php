<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return "Hello user";
});

Route::get('/weather', function () {
    $apiKey = env('OPENWEATHERMAP_API_KEY');
    $city = 'Nairobi';
    $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
        'q' => $city,
        'appid' => $apiKey,
    ]);

    if ($response->successful()) {
        return $response->json();
    }

    return response()->json(['error' => 'Unable to fetch weather data'], $response->status());
});

