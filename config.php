<?php
/**
 * Weather App Configuration File
 * 
 * IMPORTANT: Add your OpenWeatherMap API key below
 * Get a free API key at: https://openweathermap.org/api
 */

// ============================================================
// API CONFIGURATION
// ============================================================

// Priority: Environmental Variable (for Vercel/Prod) -> Hardcoded (for Local)
$env_key = getenv('WEATHER_API_KEY');
define('API_KEY', $env_key ?: 'c724801574a09099a28a564501af63d7');

// OpenWeatherMap API endpoint
define('API_URL', 'https://api.openweathermap.org/data/2.5/weather');

// Default temperature unit (metric = Celsius, imperial = Fahrenheit)
define('DEFAULT_UNIT', 'metric');

// API timeout in seconds
define('API_TIMEOUT', 10);

// ============================================================
// VALIDATION
// ============================================================

// Check if API key is set
if (API_KEY === 'YOUR_API_KEY_HERE') {
    $api_key_warning = true;
} else {
    $api_key_warning = false;
}

?>
