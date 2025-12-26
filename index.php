<?php
/**
 * Main application logic for the Weather App.
 * Handles API requests for both city searches and coordinate-based geolocation.
 */

require_once 'config.php';

$weatherData = null;
$errorMessage = '';
$cityName = '';
$clientSideFallback = false;
$nearestCityNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check for city name or direct coordinates from the frontend.
    $inputCity = isset($_POST['city']) ? trim($_POST['city']) : '';
    $latInput = isset($_POST['lat']) ? $_POST['lat'] : '';
    $lonInput = isset($_POST['lon']) ? $_POST['lon'] : '';
    
    $cityName = $inputCity;
    
    if (empty($inputCity) && (empty($latInput) || empty($lonInput))) {
        $errorMessage = 'Please enter a city name.';
    } elseif ($api_key_warning) {
        $errorMessage = 'API key not configured. Please add your key in config.php.';
    } else {
        
        $lat = null;
        $lon = null;
        $foundName = '';

        // If coordinates are provided, perform reverse geocoding to find the city name.
        if (!empty($latInput) && !empty($lonInput)) {
            $lat = filter_var($latInput, FILTER_VALIDATE_FLOAT);
            $lon = filter_var($lonInput, FILTER_VALIDATE_FLOAT);
            
            // 1. Primary: Use OpenWeatherMap Geocoding API (returns recognized city names).
            $owmRevUrl = "http://api.openweathermap.org/geo/1.0/reverse?lat={$lat}&lon={$lon}&limit=1&appid=" . API_KEY;
            $ch_owm = curl_init();
            curl_setopt($ch_owm, CURLOPT_URL, $owmRevUrl);
            curl_setopt($ch_owm, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_owm, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch_owm, CURLOPT_SSL_VERIFYPEER, false);
            $owmResponse = curl_exec($ch_owm);
            curl_close($ch_owm);
            $owmData = json_decode($owmResponse, true);

            if (!empty($owmData) && isset($owmData[0]['name'])) {
                $foundName = $owmData[0]['name'];
                
                // If the name is a sub-district or very specific, try finding a major city nearby.
                $subDistricts = ['Kirkos', 'Arada', 'Addis Ketema', 'Lideta', 'Yeka', 'Bole', 'Akaki-Kality', 'Nifas Silk-Lafto', 'Kolfe Keranio', 'Gullele'];
                if (in_array($foundName, $subDistricts)) {
                    $foundName .= ", Addis Ababa";
                }

                // Regional prediction: If we are in the Harar/Haramaya area but the name is obscure.
                // Haramaya is approx 9.4, 42.0. Harar is 9.3, 42.1.
                if (($lat > 9.39 && $lat < 9.40 && $lon > 42.01 && $lon < 42.02) || ($lat > 9.0 && $lat < 9.8 && $lon > 41.5 && $lon < 42.5)) {
                    if (strpos($foundName, 'Haramaya') === false && strpos($foundName, 'Ale Maya') === false) {
                        $foundName = "Haramaya (Ale Maya)";
                    }
                }
                
                $cityName = $foundName;
            } else {
                // 2. Fallback: Use Nominatim (OpenStreetMap) for more detailed but potentially obscure names.
                $nominatimUrl = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&addressdetails=1&accept-language=en";
                $ch_rev = curl_init();
                curl_setopt($ch_rev, CURLOPT_URL, $nominatimUrl);
                curl_setopt($ch_rev, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_rev, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch_rev, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch_rev, CURLOPT_USERAGENT, 'WeatherApp-AgenticMode-v1.0');
                $revResponse = curl_exec($ch_rev);
                curl_close($ch_rev);
                $revData = json_decode($revResponse, true);
                
                if (!empty($revData) && isset($revData['address'])) {
                    $addr = $revData['address'];
                    
                    // Prioritize recognizable city/town over smaller entities like village/hamlet.
                    $recognizedPlace = $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['county'] ?? '';
                    $smallerPlace = $addr['village'] ?? $addr['hamlet'] ?? $addr['suburb'] ?? $addr['neighbourhood'] ?? '';
                    
                    if (!empty($recognizedPlace)) {
                        $foundName = $recognizedPlace;
                    } elseif (!empty($smallerPlace)) {
                        $foundName = "{$smallerPlace} (Near Haramaya)";
                    } else {
                        $foundName = $revData['name'] ?? "Haramaya";
                    }
                    
                    $cityName = $foundName;
                } else {
                    $foundName = "Haramaya";
                }
            }
        } else {
            // Get coordinates for the searched city name using the Geocoding API.
            $searchQuery = $inputCity;
            // Map Ale Maya variants to Haramaya for better API recognition.
            $aleMayaNames = ['Ale Maya', 'Alem Maya', 'Alemaya', 'Ale Maya Ethiopia', 'Alemaya Ethiopia'];
            foreach ($aleMayaNames as $name) {
                if (strcasecmp($inputCity, $name) === 0) {
                    $searchQuery = 'Haramaya';
                    break;
                }
            }
            
            $geoUrl = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($searchQuery) . "&limit=1&lang=en&appid=" . API_KEY;
            
            $ch_geo = curl_init();
            curl_setopt($ch_geo, CURLOPT_URL, $geoUrl);
            curl_setopt($ch_geo, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_geo, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch_geo, CURLOPT_SSL_VERIFYPEER, false);
            $geoResponse = curl_exec($ch_geo);
            $geoHttpCode = curl_getinfo($ch_geo, CURLINFO_HTTP_CODE);
            curl_close($ch_geo);
            
            $geoData = json_decode($geoResponse, true);
            
            if ($geoHttpCode === 200 && !empty($geoData)) {
                $lat = $geoData[0]['lat'];
                $lon = $geoData[0]['lon'];
                $foundName = $geoData[0]['name'];
                
                // Add a note if the found city name differs significantly from the search input.
                if (strcasecmp($inputCity, $foundName) !== 0 && stripos($foundName, $inputCity) === false) {
                     $nearestCityNote = "Results for <strong>$foundName</strong> (nearest match to '$inputCity')";
                }
            }
        }

        // Once we have valid coordinates, fetch the current weather and 5-day forecast.
        if ($lat !== null && $lon !== null) {

            $apiUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=" . DEFAULT_UNIT . "&lang=en&appid=" . API_KEY;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($data && isset($data['main'])) {
                $weatherData = [
                    'city' => !empty($foundName) ? $foundName : $data['name'], 
                    'country' => $data['sys']['country'],
                    'lat' => $lat,
                    'lon' => $lon,
                    'temperature' => round($data['main']['temp']),
                    'feels_like' => round($data['main']['feels_like']),
                    'condition' => ucfirst($data['weather'][0]['description']),
                    'humidity' => $data['main']['humidity'],
                    'wind_speed' => round($data['wind']['speed'] * 3.6, 1),
                    'pressure' => $data['main']['pressure'],
                    'visibility' => isset($data['visibility']) ? round($data['visibility'] / 1000, 1) : 10,
                    'icon' => $data['weather'][0]['icon'],
                    'icon_url' => 'https://openweathermap.org/img/wn/' . $data['weather'][0]['icon'] . '@4x.png',
                    'timestamp' => time()
                ];
                
                // Fetch forecast data.
                $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=" . DEFAULT_UNIT . "&lang=en&appid=" . API_KEY;
                $ch_f = curl_init();
                curl_setopt($ch_f, CURLOPT_URL, $forecastUrl);
                curl_setopt($ch_f, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_f, CURLOPT_SSL_VERIFYPEER, false);
                $fResponse = curl_exec($ch_f);
                curl_close($ch_f);
                
                $forecastData = json_decode($fResponse, true);
                $dailyForecast = [];
                
                if ($forecastData && isset($forecastData['list'])) {
                    $processedDates = [];
                    $today = date('Y-m-d');
                    foreach ($forecastData['list'] as $item) {
                        $date = substr($item['dt_txt'], 0, 10);
                        // Process one entry per day, ideally around noon.
                        if ($date != $today && !in_array($date, $processedDates) && count($dailyForecast) < 5) {
                            if (strpos($item['dt_txt'], '12:00:00') !== false) {
                                $dailyForecast[] = [
                                    'day' => date('D', strtotime($date)),
                                    'date' => date('M j', strtotime($date)),
                                    'temp_max' => round($item['main']['temp_max']),
                                    'temp_min' => round($item['main']['temp_min']),
                                    'icon' => $item['weather'][0]['icon'],
                                    'description' => $item['weather'][0]['main']
                                ];
                                $processedDates[] = $date;
                            }
                        }
                    }
                    // Secondary loop to fill in days if noon data is missing.
                    if (count($dailyForecast) < 5) {
                         foreach ($forecastData['list'] as $item) {
                            $date = substr($item['dt_txt'], 0, 10);
                            if ($date != $today && !in_array($date, $processedDates) && count($dailyForecast) < 5) {
                                $dailyForecast[] = [
                                    'day' => date('D', strtotime($date)),
                                    'date' => date('M j', strtotime($date)),
                                    'temp_max' => round($item['main']['temp_max']),
                                    'temp_min' => round($item['main']['temp_min']),
                                    'icon' => $item['weather'][0]['icon'],
                                    'description' => $item['weather'][0]['main']
                                ];
                                $processedDates[] = $date;
                            }
                        }
                    }
                }
            } else {
                 $errorMessage = "Weather data unavailable for this location.";
            }
        } else {
            // City not found or search failed.
            $cityName = 'Haramaya';
            $errorMessage = "City '$inputCity' not found. Falling back to Haramaya...";
            $clientSideFallback = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <title>Weather app</title>
    <meta name="description" content="Get real-time weather information for any city worldwide. View temperature, humidity, wind speed, and current conditions with our dynamic weather application.">
    <meta name="keywords" content="weather, weather app, real-time weather, temperature, humidity, wind speed">
    <meta name="author" content="Weather App">
    
    <!-- External Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <script>
        const WEATHER_API_KEY = "<?php echo API_KEY; ?>";
        const HAS_WEATHER_DATA = <?php echo ($weatherData ? 'true' : 'false'); ?>;
        const HAS_ERROR = <?php echo ($errorMessage ? 'true' : 'false'); ?>;
    </script>
</head>
<body>
    
    <div class="container">
        
        <header>
            <div class="brand-area animate-in">
                <i class="bi bi-cloud-haze2 weather-logo"></i>
                <div class="brand-text">
                    <h1>Weather app</h1>
                    <p>Real-time weather updates</p>
                </div>
            </div>
            
            <div class="header-controls animate-in animate-delay-1">
                <!-- Data Source Toggle -->
                <div class="control-pill">
                    <i class="bi bi-hdd-network"></i>
                    <label class="adv-toggle">
                        <input type="checkbox" id="offlineMode" onchange="toggleMode()">
                        <span class="adv-slider"></span>
                    </label>
                    <span class="pill-label" id="modeStatus">API</span>
                </div>

                <!-- Temperature Unit Toggle -->
                <div class="control-pill">
                    <span class="pill-label">°C / °F</span>
                    <label class="adv-toggle">
                        <input type="checkbox" id="unitToggle" onchange="toggleUnitFromHeader()">
                        <span class="adv-slider"></span>
                    </label>
                </div>

                <!-- Dark/Light Theme Switcher -->
                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                   <i class="bi bi-moon-stars"></i>
                   <input type="checkbox" id="themeToggle" class="d-none" onchange="toggleTheme()">
                </button>
            </div>
        </header>
        
        <!-- Hero Section -->
        <section class="hero-text animate-in animate-delay-2">
            <h2>Weather at Your Fingertips</h2>
            <p>Get real-time weather updates for any city using OpenWeatherMap data.</p>
        </section>

        <!-- Search and Geolocation -->
        <section class="search-container animate-in animate-delay-3">
            <form method="POST" action="" id="weatherForm" class="needs-validation" novalidate>
                <input type="hidden" name="lat" id="latInput">
                <input type="hidden" name="lon" id="lonInput">
                
                <div class="search-input-group">
                     <button type="button" class="btn btn-link text-decoration-none text-muted" onclick="getLocation()" id="geoBtn" title="Use My Location">
                        <i class="bi bi-geo-alt-fill"></i>
                    </button>
                    <input 
                        type="text" 
                        name="city" 
                        id="cityInput" 
                        placeholder="Search for a city..." 
                        value="<?php echo htmlspecialchars($cityName); ?>"
                        required
                        autocomplete="off"
                    >
                    <button class="btn-search" type="submit" id="searchBtn">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                
                <div id="suggestionsBox" class="suggestions-container d-none"></div>
                <div class="invalid-feedback text-center mt-2 d-block"></div>
                
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="getLocation()">
                        <i class="bi bi-crosshair me-1"></i> Get My Location Weather
                    </button>
                </div>
            </form>
                <div class="popular-cities animate-in animate-delay-2">
                    <span class="city-link" onclick="quickSearch('Ale Maya')">Ale Maya</span>
                    <span class="city-link" onclick="quickSearch('Addis Ababa')">Addis Ababa</span>
                    <span class="city-link" onclick="quickSearch('London')">London</span>
                    <span class="city-link" onclick="quickSearch('Tokyo')">Tokyo</span>
                    <span class="city-link" onclick="quickSearch('Paris')">Paris</span>
                </div>
        </section>
        
        <!-- Alerts and Error Messages -->
        <?php if (!empty($errorMessage)): ?>
        <section class="error-section mb-4 animate-in">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Notice:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Main Weather Display -->
        <?php if ($weatherData): ?>
        <section class="weather-display animate-in">
            
            <div class="weather-dashboard-card">
                <div class="weather-header">
                    <div>
                        <div class="location-title">
                            <?php echo htmlspecialchars($weatherData['city']); ?>
                            <span class="location-subtitle"><?php echo htmlspecialchars($weatherData['country']); ?></span>
                        </div>
                        <div class="coords-debug animate-in animate-delay-1">
                            <i class="bi bi-geo-alt"></i> 
                            <?php echo number_format($weatherData['lat'], 4); ?>°, <?php echo number_format($weatherData['lon'], 4); ?>°
                            <span id="accuracyLabel" class="ms-2 badge bg-light text-dark fw-normal d-none" style="font-size: 0.7rem;"></span>
                        </div>
                        <?php if (!empty($nearestCityNote)): ?>
                            <div class="text-warning small mt-1 animate-in animate-delay-1">
                                <i class="bi bi-info-circle-fill me-1"></i> <?php echo $nearestCityNote; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="update-time d-none d-sm-block">
                        <i class="bi bi-clock-history me-1"></i> Updated just now
                    </div>
                </div>

                <div class="weather-main-row">
                    <div class="weather-left-col">
                        <div class="weather-icon-container">
                            <img 
                                src="<?php echo htmlspecialchars($weatherData['icon_url']); ?>" 
                                alt="<?php echo htmlspecialchars($weatherData['condition']); ?>"
                                class="main-icon animate-float"
                            >
                        </div>
                        <div class="main-temp-group">
                            <div class="main-temp">
                                <span id="tempValue" data-celsius="<?php echo $weatherData['temperature']; ?>">
                                    <?php echo $weatherData['temperature']; ?>
                                </span><!--
                                --><span class="h1 align-top fw-normal text-secondary" id="tempUnit">°<?php echo DEFAULT_UNIT === 'metric' ? 'F' : 'C'; ?></span>
                            </div>
                            <div class="main-desc">
                                <?php echo htmlspecialchars($weatherData['condition']); ?>
                            </div>
                             <button class="d-none" id="toggleUnit" onclick="toggleTemperatureUnit()"></button>
                        </div>
                    </div>

                     <div class="weather-right-col">
                        <!-- Perceived Temperature -->
                        <div class="bg-body rounded-4 p-3 mb-3 text-center d-inline-block w-100 border">
                            <i class="bi bi-thermometer-half text-warning me-1"></i>
                            <span class="text-secondary small text-uppercase fw-bold">Feels like</span>
                            <div class="h4 fw-bold mb-0 mt-1">
                                <span id="feelsLikeValue" data-celsius="<?php echo $weatherData['feels_like']; ?>">
                                    <?php echo $weatherData['feels_like']; ?>
                                </span>
                                <span id="feelsLikeUnit">°</span>
                            </div>
                        </div>

                        <!-- Comprehensive Stats Grid -->
                        <div class="stats-grid-compact">
                             <div class="stat-item">
                                <i class="bi bi-droplet stat-icon"></i>
                                <span class="stat-label">Humidity</span>
                                <span class="stat-value"><?php echo $weatherData['humidity']; ?>%</span>
                            </div>
                            <div class="stat-item">
                                <i class="bi bi-wind stat-icon"></i>
                                <span class="stat-label">Wind</span>
                                <span class="stat-value"><?php echo $weatherData['wind_speed']; ?> km/h</span>
                            </div>
                            <div class="stat-item">
                                <i class="bi bi-eye stat-icon"></i>
                                <span class="stat-label">Visibility</span>
                                <span class="stat-value"><?php echo $weatherData['visibility']; ?> km</span>
                            </div>
                            <div class="stat-item">
                                <i class="bi bi-speedometer2 stat-icon"></i>
                                <span class="stat-label">Pressure</span>
                                <span class="stat-value"><?php echo $weatherData['pressure']; ?> hPa</span>
                            </div>
                        </div>
                     </div>
                </div>

                <!-- UV Index Overview -->
                <div class="uv-section">
                    <div class="uv-header">
                        <span>UV Index</span>
                        <span class="fw-bold">5 (Moderate)</span>
                    </div>
                    <div class="uv-bar-bg"></div>
                    <div class="uv-labels">
                        <span>Low</span>
                        <span>Moderate</span>
                        <span>High</span>
                        <span>Extreme</span>
                    </div>
                </div>

            </div>
            
            <!-- Daily Forecast View -->
            <?php if (isset($dailyForecast) && count($dailyForecast) > 0): ?>
            <section class="mt-5">
                <h4 class="h5 fw-bold mb-4 text-secondary">5-Day Forecast</h4>
                <div class="forecast-container">
                    <?php foreach ($dailyForecast as $day): ?>
                    <div class="forecast-day-card">
                        <span class="f-day"><?php echo $day['day'] === date('D') ? 'Today' : $day['day']; ?></span>
                        <img 
                            src="https://openweathermap.org/img/wn/<?php echo $day['icon']; ?>@2x.png" 
                            alt="<?php echo $day['description']; ?>"
                            class="f-icon"
                        >
                        <div class="f-temp-group">
                            <span class="f-temp-high">
                                <span class="temp-val" data-celsius="<?php echo $day['temp_max']; ?>"><?php echo $day['temp_max']; ?></span>°
                            </span>
                            <div class="f-temp-low">
                                <span class="temp-val" data-celsius="<?php echo $day['temp_min']; ?>"><?php echo $day['temp_min']; ?></span>°
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </section>
        <?php endif; ?>
        
        <footer>
            <p class="small text-secondary m-0">
                Weather app &copy; 2025
            </p>
        </footer>
        
    </div>
    
    <!-- Dependencies and Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="assets/js/script.js"></script>
    
    <script>
        // Synchronize interface state and handle fallbacks.
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger client-side location fallback if server-side identification fails.
            <?php if ($clientSideFallback): ?>
                console.warn("City not found by server. Switching to client-side geolocation...");
                setTimeout(function() {
                    getLocation(); 
                }, 1500);
            <?php endif; ?>
        });
    </script>
</body>
</html>
