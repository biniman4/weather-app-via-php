<?php
/**
 * Dynamic Weather Info Display - Main Application
 * 
 * This application fetches and displays real-time weather data
 * using the OpenWeatherMap API with PHP, Bootstrap, and JavaScript.
 * 
 * @author Academic Project
 * @version 1.0
 */

// Include configuration file
require_once 'config.php';

// Initialize variables
// ============================================================
// PHP SERVER-SIDE LOGIC - ROBUST GEOCODING STRATEGY
// ============================================================

$weatherData = null;
$errorMessage = '';
$cityName = '';
$clientSideFallback = false;
$nearestCityNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if searching by city name OR direct coordinates (Geolocation)
    $inputCity = isset($_POST['city']) ? trim($_POST['city']) : '';
    $latInput = isset($_POST['lat']) ? $_POST['lat'] : '';
    $lonInput = isset($_POST['lon']) ? $_POST['lon'] : '';
    
    $cityName = $inputCity;
    
    if (empty($inputCity) && (empty($latInput) || empty($lonInput))) {
        $errorMessage = 'Please enter a city name.';
    } elseif ($api_key_warning) {
        $errorMessage = 'API key not configured. Please add your OpenWeatherMap API key in config.php file.';
    } else {
        
        $lat = null;
        $lon = null;
        $foundName = '';

        // If coordinates provided, use them directly
        if (!empty($latInput) && !empty($lonInput)) {
            $lat = filter_var($latInput, FILTER_VALIDATE_FLOAT);
            $lon = filter_var($lonInput, FILTER_VALIDATE_FLOAT);
            
            // STEP 1.5: REVERSE GEOCODING (Using Nominatim for pinpoint accuracy)
            $nominatimUrl = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&addressdetails=1&accept-language=en";
            $ch_rev = curl_init();
            curl_setopt($ch_rev, CURLOPT_URL, $nominatimUrl);
            curl_setopt($ch_rev, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_rev, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch_rev, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch_rev, CURLOPT_USERAGENT, 'WeatherApp-AgenticMode-v1.0'); // Required by Nominatim policy
            $revResponse = curl_exec($ch_rev);
            curl_close($ch_rev);
            $revData = json_decode($revResponse, true);
            
            if (!empty($revData) && isset($revData['address'])) {
                $addr = $revData['address'];
                // Priority: village/town/suburb over city to get "Haramaya" correctly
                $foundName = $addr['village'] ?? $addr['hamlet'] ?? $addr['town'] ?? $addr['suburb'] ?? $addr['city'] ?? $addr['municipality'] ?? $revData['name'] ?? "Current Location";
                $cityName = $foundName;
            } else {
                $foundName = "Current Location";
            }
        } else {
            // STEP 1: DIRECT GEOCODING (To validate city & get coords)
            $geoUrl = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($inputCity) . "&limit=1&lang=en&appid=" . API_KEY;
            
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
                
                if (strcasecmp($inputCity, $foundName) !== 0 && stripos($foundName, $inputCity) === false) {
                     $nearestCityNote = "Results for <strong>$foundName</strong> (nearest match to '$inputCity')";
                }
            }
        }

        // Validate Coordinates exist before step 2
        if ($lat !== null && $lon !== null) {

            // STEP 2: FETCH WEATHER WITH COORDINATES
            $apiUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=" . DEFAULT_UNIT . "&lang=en&appid=" . API_KEY;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($data && isset($data['main'])) {
                 // Successfully retrieved weather data
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
                
                // STEP 3: FETCH FORECAST (Using Coords)
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
                    // Fallback loop if noon data missing
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
            // GEOCODING FAILED (City not found) -> TRIGGER FALLBACK
            $errorMessage = "City '$inputCity' not found. Attempting to locate you...";
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
    <title>Weather app</title>
    <link rel="icon" type="image/png" href="assets/images/weather app icon.png">
    <meta name="description" content="Get real-time weather information for any city worldwide. View temperature, humidity, wind speed, and current conditions with our dynamic weather application.">
    <meta name="keywords" content="weather, weather app, real-time weather, temperature, humidity, wind speed">
    <meta name="author" content="Weather App">
    
    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Pass PHP Configuration to JavaScript -->
    <script>
        const WEATHER_API_KEY = "<?php echo API_KEY; ?>";
    </script>
</head>
<body>
    
    <!-- Main Container -->
    <div class="container">
        
        <!-- Header Section -->
        <header>
            <div class="brand-area animate-in">
                <i class="bi bi-cloud-haze2 weather-logo"></i>
                <div class="brand-text">
                    <h1>Weather app</h1>
                    <p>Real-time weather updates</p>
                </div>
            </div>
            
            <!-- Header Controls (Pills) -->
            <div class="header-controls animate-in animate-delay-1">
                <!-- Mode Toggle (Static/API) -->
                <div class="control-pill">
                    <i class="bi bi-hdd-network"></i>
                    <div class="form-check form-switch m-0 min-h-0">
                        <input class="form-check-input" type="checkbox" id="offlineMode" onchange="toggleMode()">
                        <label class="form-check-label small" for="offlineMode" id="modeStatus">API</label>
                    </div>
                </div>

                <!-- Unit Toggle -->
                <div class="control-pill">
                    <span class="small fw-bold">°C / °F</span>
                    <button class="theme-toggle-btn ms-1" onclick="document.getElementById('unitToggle').click()">
                        <div class="form-check form-switch m-0 min-h-0">
                            <!-- Hidden checkbox that drives logic -->
                            <input class="form-check-input" type="checkbox" id="unitToggle" onchange="toggleUnitFromHeader()">
                        </div>
                    </button>
                </div>

                <!-- Theme Toggle -->
                <button class="theme-toggle-btn p-2 rounded-circle border" onclick="toggleTheme()" title="Toggle Theme">
                   <i class="bi bi-moon-stars"></i>
                   <!-- Hidden checkbox for state tracking -->
                   <input type="checkbox" id="themeToggle" class="d-none" onchange="toggleTheme()">
                </button>
            </div>
        </header>
        
        <!-- Hero Section -->
        <section class="hero-text animate-in animate-delay-2">
            <h2>Weather at Your Fingertips</h2>
            <p>Get real-time weather updates for any city. Currently using <span class="fw-bold text-primary">OpenWeatherMap</span>.</p>
        </section>

        <!-- Search Section -->
        <section class="search-container animate-in animate-delay-3">
            <form method="POST" action="" id="weatherForm" class="needs-validation" novalidate>
                <!-- Geolocation Hidden Coords -->
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
                
                <!-- Dynamic Suggestions Container (Outside Input Group, Inside Form) -->
                <div id="suggestionsBox" class="suggestions-container d-none"></div>
                <div class="invalid-feedback text-center mt-2 d-block">
                    <!-- Validation messages appear here -->
                </div>
                
                <!-- Explicit Geolocation Button -->
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="getLocation()">
                        <i class="bi bi-crosshair me-1"></i> Get My Location Weather
                    </button>
                </div>
            </form>
            
            <!-- Quick Cities -->
            <div class="popular-cities">
                <span class="city-link" onclick="quickSearch('New York')">New York</span>
                <span class="city-link" onclick="quickSearch('London')">London</span>
                <span class="city-link" onclick="quickSearch('Tokyo')">Tokyo</span>
                <span class="city-link" onclick="quickSearch('Paris')">Paris</span>
                <span class="city-link" onclick="quickSearch('Dubai')">Dubai</span>
            </div>
        </section>
        
        <!-- Error Message Section -->
        <?php if (!empty($errorMessage)): ?>
        <section class="error-section mb-4 animate-in">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Note:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Weather Display Section -->
        <?php if ($weatherData): ?>
        <section class="weather-display animate-in">
            
            <!-- Main Dashboard Card -->
            <div class="weather-dashboard-card">
                <!-- Location Header -->
                <div class="weather-header">
                    <div>
                        <div class="location-title">
                            <?php echo htmlspecialchars($weatherData['city']); ?>
                            <span class="location-subtitle ms-2"><?php echo htmlspecialchars($weatherData['country']); ?></span>
                        </div>
                        <?php if(isset($weatherData['lat'])): ?>
                            <div class="coords-debug small text-muted mb-2">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?php echo number_format($weatherData['lat'], 4); ?>°, <?php echo number_format($weatherData['lon'], 4); ?>°
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($nearestCityNote)): ?>
                            <div class="text-warning small mt-1 animate-in animate-delay-1">
                                <i class="bi bi-info-circle-fill me-1"></i> <?php echo $nearestCityNote; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="update-time">
                        <i class="bi bi-clock me-1"></i> Updated just now
                    </div>
                </div>

                <!-- Main Content Row -->
                <div class="weather-main-row">
                    <!-- Left: Icon & Temp -->
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
                                --><span class="h1 align-top fw-normal text-secondary" id="tempUnit">°<?php echo DEFAULT_UNIT === 'metric' ? 'F' : 'C'; // Note: Display logic might be inverted in script, defaulting to F per mockup ?></span>
                            </div>
                            <div class="main-desc">
                                <?php echo htmlspecialchars($weatherData['condition']); ?>
                            </div>
                            <!-- Hidden button for script compatibility -->
                             <button class="d-none" id="toggleUnit" onclick="toggleTemperatureUnit()"></button>
                        </div>
                    </div>

                    <!-- Right: Stats Grid (Feels like + Others) -->
                     <div class="weather-right-col">
                        <!-- Feels Like Pill -->
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

                        <!-- Grid -->
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

                <!-- UV Section (Static Mockup) -->
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
            
            <!-- 5-Day Forecast -->
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
        
        <!-- Footer -->
        <footer class="text-center mt-auto pb-4">
            <p class="small text-secondary m-0">
                Weather app &copy; 2025
            </p>
        </footer>
        
    </div>
    
    <!-- Bootstrap 5 JS Bundle (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <script>
        // Init logic for toggles visual state
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure temp unit shows correctly on load
            if(document.getElementById('tempUnit') && document.getElementById('tempUnit').innerText.includes('C')) {
                 document.getElementById('unitToggle').checked = false; 
            }

            // AUTO-FALLBACK TRIGGER
            // If PHP couldn't find the city, it sets $clientSideFallback = true
            <?php if ($clientSideFallback): ?>
                console.warn("City not found by server. Triggering auto-location fallback.");
                // We add a small delay to let the UI render the error message first
                setTimeout(function() {
                    getLocation(); 
                }, 1500);
            <?php endif; ?>
        });
    </script>
</body>
</html>
