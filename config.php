<?php
/**
 * Configuration for the Weather App.
 * API keys and global constants are defined here.
 */

// Load the API key from environment variables (for production) or use the fallback (for local development).
$env_key = getenv('WEATHER_API_KEY');
define('API_KEY', $env_key ?: 'c724801574a09099a28a564501af63d7');

// OpenWeatherMap API endpoint and defaults.
define('API_URL', 'https://api.openweathermap.org/data/2.5/weather');
define('DEFAULT_UNIT', 'metric'); // 'metric' for Celsius, 'imperial' for Fahrenheit.
define('API_TIMEOUT', 10);

// Basic check to see if the API key is properly configured.
$api_key_warning = (API_KEY === 'YOUR_API_KEY_HERE');

?>
