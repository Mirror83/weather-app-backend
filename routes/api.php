<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return "Hello user";
});

Route::get('/weather/{city}', function (string $city) {
    $apiKey = env('OPENWEATHERMAP_API_KEY');

    // Get co-ordinates from the Geocoding API
    $geocodeResponse = Http::get("https://api.openweathermap.org/data/2.5/weather", [
        'q' => $city,
        'appid' => $apiKey,
        'units' => "metric"
    ]);

    if ($geocodeResponse->successful()) {
        $coordinates = $geocodeResponse->json();
        $lat = $coordinates['coord']['lat'];
        $lon = $coordinates['coord']['lon'];
    } else {
        return response()->json(['error' => 'Unable to fetch coordinates'], $geocodeResponse->status());
    }

    // Use the longitude and latitude to get the weather data
    $weatherDataResponse = Http::get("https://api.openweathermap.org/data/2.5/weather", [
        'lat' => $lat,
        'lon' => $lon,
        'appid' => $apiKey,
        'units' => 'metric'
    ]);

    // Check if the response is successful
    if ($weatherDataResponse->successful()) {
        $weatherData = $weatherDataResponse->json();
        $weatherData["weather"] = $weatherData["weather"][0];
    } else {
        return response()->json(['error' => 'Unable to fetch weather data'], $weatherDataResponse->status());
    }
    return response()->json([
        'current' => $weatherData,
    ]);
});

