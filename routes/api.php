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

    // Also, use the 5 day forecast API to get the forecast for the next 3 days
    $forecastResponse = Http::get("https://api.openweathermap.org/data/2.5/forecast", [
        'lat' => $lat,
        'lon' => $lon,
        'appid' => $apiKey,
        'cnt' => 24, // Get the next 3 days of forecast
        'units' => 'metric'
    ]);

    if ($forecastResponse->successful()) {
        $forecastData = $forecastResponse->json()['list'];

        // Group forecasts by day and calculate averages
        $dailyAverages = [];
        foreach ($forecastData as $forecast) {
            $date = date('Y-m-d', strtotime($forecast['dt_txt']));
            if (!isset($dailyAverages[$date])) {
                $dailyAverages[$date] = [
                    'min_temp' => PHP_FLOAT_MAX,
                    'max_temp' => PHP_FLOAT_MIN,
                    'temp' => 0,
                    'count' => 0,
                    'weather' => [],
                ];
            }

            $dailyAverages[$date]['min_temp'] = min($dailyAverages[$date]['min_temp'], $forecast['main']['temp_min']);
            $dailyAverages[$date]['max_temp'] = max($dailyAverages[$date]['max_temp'], $forecast['main']['temp_max']);
            $dailyAverages[$date]['temp'] += $forecast['main']['temp'];
            $dailyAverages[$date]['count']++;

            // Collect weather descriptions and icons
            $weatherDescription = $forecast['weather'][0]['description'];
            $weatherIcon = $forecast['weather'][0]['icon'];
            $weatherKey = $weatherDescription . '|' . $weatherIcon;

            if (!isset($dailyAverages[$date]['weather'][$weatherKey])) {
                $dailyAverages[$date]['weather'][$weatherKey] = 0;
            }
            $dailyAverages[$date]['weather'][$weatherKey]++;
        }

        // Calculate the average temperature and determine the most frequent weather for each day
        $averages = [];
        foreach ($dailyAverages as $date => $data) {
            // Determine the most frequent weather description and icon
            $mostFrequentWeather = array_keys($data['weather'], max($data['weather']))[0];
            [$description, $icon] = explode('|', $mostFrequentWeather);

            $averages[] = [
                'date' => $date,
                'average_temp' => $data['temp'] / $data['count'],
                'min_temp' => $data['min_temp'],
                'max_temp' => $data['max_temp'],
                'weather' => [
                    'description' => $description,
                    'icon' => $icon,
                ],
            ];
        }

        // Limit to the next 3 days
        $averages = array_slice($averages, 0, 3);
    } else {
        return response()->json(['error' => 'Unable to fetch forecast data'], $forecastResponse->status());
    }

    // Return the weather data and forecast
    return response()->json([
        'current' => $weatherData,
        'forecast' => $averages,
    ]);
});

