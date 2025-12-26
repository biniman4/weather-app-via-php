<?php
/**
 * User Dashboard - Redirects to Home Page
 * The dashboard is the same as the homepage, just with user logged in
 */

require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

startSecureSession();
requireLogin('dashboard.php');

// Redirect to homepage - the dashboard IS the homepage when logged in
header("Location: index.php" . (isset($_GET['welcome']) ? '?welcome=1' : ''));
exit;

?>
$cityName = '';
$clientSideFallback = false;
$nearestCityNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
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

        if (!empty($latInput) && !empty($lonInput)) {
            $lat = filter_var($latInput, FILTER_VALIDATE_FLOAT);
            $lon = filter_var($lonInput, FILTER_VALIDATE_FLOAT);
            
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
                $cityName = $foundName;
            } else {
                $foundName = "Haramaya";
            }
        } else {
            $searchQuery = $inputCity;
            $geoUrl = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($searchQuery) . "&limit=1&lang=en&appid=" . API_KEY;
            
            $ch_geo = curl_init();
            curl_setopt($ch_geo, CURLOPT_URL, $geoUrl);
            curl_setopt($ch_geo, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_geo, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch_geo, CURLOPT_SSL_VERIFYPEER, false);
            $geoResponse = curl_exec($ch_geo);
            curl_close($ch_geo);
            
            $geoData = json_decode($geoResponse, true);
            
            if (!empty($geoData)) {
                $lat = $geoData[0]['lat'];
                $lon = $geoData[0]['lon'];
                $foundName = $geoData[0]['name'];
            }
        }

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
                
                // Fetch forecast
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
                }
            } else {
                 $errorMessage = "Weather data unavailable for this location.";
            }
        } else {
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
    <title>Dashboard - Weather App</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <h1>Weather Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?>!</p>
                </div>
            </div>
            
            <div class="header-controls animate-in animate-delay-1">
                <a href="index.php" class="btn-auth" style="text-decoration: none; padding: 8px 18px; background: rgba(255, 255, 255, 0.9); color: #667eea; border-radius: 8px; font-size: 0.9rem; font-weight: 600; border: 2px solid #667eea;">
                    <i class="bi bi-house"></i> Home
                </a>
                <a href="logout.php" class="btn-auth" style="text-decoration: none; padding: 8px 18px; background: #e74c3c; color: white; border-radius: 8px; font-size: 0.9rem; font-weight: 600;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
                
                <div class="control-pill">
                    <span class="pill-label">°C / °F</span>
                    <label class="adv-toggle">
                        <input type="checkbox" id="unitToggle" onchange="toggleUnitFromHeader()">
                        <span class="adv-slider"></span>
                    </label>
                </div>

                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                   <i class="bi bi-moon-stars"></i>
                   <input type="checkbox" id="themeToggle" class="d-none" onchange="toggleTheme()">
                </button>
            </div>
        </header>
        
        <?php if ($welcomeMessage): ?>
        <section class="animate-in">
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Welcome!</strong> Your account has been created successfully. Start exploring weather updates!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </section>
        <?php endif; ?>
        
        <section class="hero-text animate-in animate-delay-2">
            <h2>Your Personal Weather Hub</h2>
            <p>Track weather for your favorite locations</p>
        </section>

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
        
        <?php if (!empty($errorMessage)): ?>
        <section class="error-section mb-4 animate-in">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Notice:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($weatherData): ?>
        <section class="weather-display animate-in">
            
            <div class="weather-dashboard-card">
                <div class="weather-header">
                    <div>
                        <div class="location-title">
                            <?php echo htmlspecialchars($weatherData['city']); ?>
                            <span class="location-subtitle"><?php echo htmlspecialchars($weatherData['country']); ?></span>
                        </div>
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
                        </div>
                    </div>

                     <div class="weather-right-col">
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

            </div>
            
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    
</body>
</html>
