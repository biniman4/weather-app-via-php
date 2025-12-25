/**
 * Dynamic Weather Info Display - JavaScript
 * 
 * Client-side functionality for temperature unit conversion,
 * form validation, and enhanced user interactions.
 */

// ============================================================
// GLOBAL VARIABLES
// ============================================================

let isCelsius = true;
let isOfflineMode = false;
let staticWeatherData = null;
let currentWeatherCondition = 'clear';

// ============================================================
// THEME TOGGLE FUNCTIONALITY
// ============================================================

/**
 * Toggle between light and dark mode
 */
function toggleTheme() {
    const currentTheme = document.body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);

    logDebug(`Switched to ${newTheme} mode`);
}

/**
 * Load saved theme preference
 */
function loadSavedTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);

    // Update toggle checkbox state
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.checked = savedTheme === 'dark';
    }
}

/**
 * Set weather-specific background
 */
function setWeatherBackground(condition) {
    const weatherMap = {
        'clear': 'clear',
        'sunny': 'sunny',
        'clouds': 'cloudy',
        'cloudy': 'cloudy',
        'rain': 'rainy',
        'drizzle': 'rainy',
        'thunderstorm': 'stormy',
        'snow': 'snowy',
        'mist': 'cloudy',
        'fog': 'cloudy',
        'haze': 'cloudy',
        'wind': 'windy'
    };

    const normalizedCondition = condition.toLowerCase();
    let weatherType = 'clear';

    for (const [key, value] of Object.entries(weatherMap)) {
        if (normalizedCondition.includes(key)) {
            weatherType = value;
            break;
        }
    }

    document.body.setAttribute('data-weather', weatherType);
    currentWeatherCondition = weatherType;
}

// ============================================================
// GEOLOCATION FUNCTIONALITY
// ============================================================

/**
 * Get user's current location and search weather
 */
function getLocation() {
    const geoBtn = document.getElementById('geoBtn');

    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    // Show loading state
    geoBtn.disabled = true;
    geoBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Detecting...';

    navigator.geolocation.getCurrentPosition(
        // Success callback
        async function (position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            try {
                // Submit coordinates directly to PHP to bypass browser CORS/Geocoding issues
                const latInput = document.getElementById('latInput');
                const lonInput = document.getElementById('lonInput');
                const cityInput = document.getElementById('cityInput');

                if (latInput && lonInput) {
                    latInput.value = lat;
                    lonInput.value = lon;

                    // Show a status in the input (optional)
                    if (cityInput) cityInput.value = "Current Location";

                    logDebug(`Submitting Geolocation: ${lat}, ${lon}`);
                    document.getElementById('weatherForm').submit();
                } else {
                    throw new Error('Form hidden fields (lat/lon) missing.');
                }
            } catch (error) {
                console.error('Geolocation Flow Error:', error);
                alert(`Error: ${error.message}. Please search manually.`);
            } finally {
                geoBtn.disabled = false;
                geoBtn.innerHTML = '<i class=\"bi bi-geo-alt-fill me-2\"></i>Use My Location';
            }
        },
        // Error callback
        function (error) {
            let message = 'Unable to retrieve your location.';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location permission denied. Please allow location access in your browser settings.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information is unavailable.';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out.';
                    break;
            }
            console.error('Geolocation Position Error:', error.message, error.code);
            alert(message);
            geoBtn.disabled = false;
            geoBtn.innerHTML = '<i class=\"bi bi-geo-alt-fill me-2\"></i>Use My Location';
        },
        {
            timeout: 10000,
            maximumAge: 0,
            enableHighAccuracy: true
        }
    );
}

/**
 * Quick search for popular cities
 */
function quickSearch(cityName) {
    const cityInput = document.getElementById('cityInput');
    cityInput.value = cityName;

    // Submit form based on mode
    if (isOfflineMode) {
        searchOfflineWeather(cityName);
    } else {
        document.getElementById('weatherForm').submit();
    }
}

// ============================================================
// TEMPERATURE UNIT TOGGLE (Header Toggle)
// ============================================================

/**
 * Toggle temperature unit from header switch
 */
function toggleUnitFromHeader() {
    const unitToggle = document.getElementById('unitToggle');
    isCelsius = !unitToggle.checked;

    // If weather data is displayed, update it
    const tempValue = document.getElementById('tempValue');
    if (tempValue) {
        toggleTemperatureUnit();
    }
}

// ============================================================
// OFFLINE MODE FUNCTIONALITY
// ============================================================

/**
 * Toggle between API and Offline mode
 */
function toggleMode() {
    const checkbox = document.getElementById('offlineMode');
    const modeStatus = document.getElementById('modeStatus');

    isOfflineMode = checkbox.checked;

    if (isOfflineMode) {
        // Switch to Offline Mode
        modeStatus.innerHTML = '<i class="bi bi-wifi-off me-1"></i> Offline Mode';

        // Load static weather data
        loadStaticWeatherData();

        logDebug('Switched to Offline Mode');
    } else {
        // Switch to API Mode
        modeStatus.innerHTML = '<i class="bi bi-wifi me-1"></i> API Mode';

        logDebug('Switched to API Mode');
    }
}

/**
 * Load static weather data from JSON file
 */
async function loadStaticWeatherData() {
    try {
        const response = await fetch('assets/data/sample-weather.json');
        const data = await response.json();
        staticWeatherData = data.cities;
        logDebug(`Loaded ${staticWeatherData.length} cities from static data`);
    } catch (error) {
        console.error('Error loading static weather data:', error);
        alert('Error: Could not load offline weather data. Please check that sample-weather.json exists.');
    }
}

/**
 * Search for city in static weather data (offline mode)
 */
function searchOfflineWeather(cityName) {
    if (!staticWeatherData) {
        alert('Static weather data not loaded. Please wait or refresh the page.');
        return null;
    }

    // Search for city (case-insensitive)
    const city = staticWeatherData.find(c =>
        c.name.toLowerCase() === cityName.toLowerCase()
    );

    if (city) {
        displayOfflineWeather(city);
        return city;
    } else {
        // Show available cities
        const availableCities = staticWeatherData.map(c => c.name).join(', ');
        showOfflineError(cityName, availableCities);
        return null;
    }
}

/**
 * Display weather data from offline mode
 */
function displayOfflineWeather(cityData) {
    // Build HTML for weather display
    const weatherHTML = `
        <section class="weather-display animate-fade-in">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    
                    <!-- Offline Mode Badge -->
                    <div class="alert alert-info mb-3" role="alert">
                        <i class="bi bi-wifi-off me-2"></i>
                        <strong>Offline Mode:</strong> Displaying static demo data
                    </div>
                    
                    <!-- Main Weather Card -->
                    <div class="weather-card glass-card p-5 mb-4">
                        <div class="row align-items-center">
                            
                            <!-- Temperature Section -->
                            <div class="col-md-6 text-center text-md-start mb-4 mb-md-0">
                                <div class="location-info mb-3">
                                    <h2 class="city-name mb-1">
                                        <i class="bi bi-geo-alt-fill me-2"></i>
                                        ${cityData.name}, ${cityData.country}
                                    </h2>
                                    <p class="condition-text mb-0">
                                        ${cityData.condition}
                                    </p>
                                </div>
                                
                                <div class="temperature-display">
                                    <div class="temp-value">
                                        <span class="temp-number" id="tempValue" data-celsius="${cityData.temperature}">
                                            ${cityData.temperature}
                                        </span>
                                        <span class="temp-unit">
                                            <span id="tempUnit">°C</span>
                                        </span>
                                    </div>
                                    <p class="feels-like text-muted">
                                        Feels like 
                                        <span id="feelsLikeValue" data-celsius="${cityData.feels_like}">
                                            ${cityData.feels_like}
                                        </span>
                                        <span id="feelsLikeUnit">°C</span>
                                    </p>
                                </div>
                                
                                <div class="unit-toggle mt-3">
                                    <button class="btn btn-outline-light btn-sm" id="toggleUnit" onclick="toggleTemperatureUnit()">
                                        <i class="bi bi-thermometer-half me-1"></i>
                                        Switch to °F
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Weather Icon Section -->
                            <div class="col-md-6 text-center">
                                <div class="weather-icon-container">
                                    <img 
                                        src="https://openweathermap.org/img/wn/${cityData.icon}@4x.png" 
                                        alt="${cityData.condition}"
                                        class="weather-icon animate-float"
                                        loading="lazy"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Weather Details Grid -->
                    <div class="row g-4">
                        
                        <!-- Humidity Card -->
                        <div class="col-md-4 col-sm-6">
                            <div class="detail-card glass-card p-4 text-center">
                                <div class="detail-icon mb-3">
                                    <i class="bi bi-droplet-fill"></i>
                                </div>
                                <h3 class="detail-label">Humidity</h3>
                                <p class="detail-value">${cityData.humidity}%</p>
                            </div>
                        </div>
                        
                        <!-- Wind Speed Card -->
                        <div class="col-md-4 col-sm-6">
                            <div class="detail-card glass-card p-4 text-center">
                                <div class="detail-icon mb-3">
                                    <i class="bi bi-wind"></i>
                                </div>
                                <h3 class="detail-label">Wind Speed</h3>
                                <p class="detail-value">${cityData.wind_speed} km/h</p>
                            </div>
                        </div>
                        
                        <!-- Pressure Card -->
                        <div class="col-md-3 col-sm-6">
                            <div class="detail-card glass-card p-4 text-center">
                                <div class="detail-icon mb-3">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <h3 class="detail-label">Pressure</h3>
                                <p class="detail-value">${cityData.pressure} hPa</p>
                            </div>
                        </div>
                        
                        <!-- Visibility Card -->
                        <div class="col-md-3 col-sm-6">
                            <div class="detail-card glass-card p-4 text-center">
                                <div class="detail-icon mb-3">
                                    <i class="bi bi-eye-fill"></i>
                                </div>
                                <h3 class="detail-label">Visibility</h3>
                                <p class="detail-value">10 km</p>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- 5-Day Forecast Section (Simulated for Offline Mode) -->
                    <section class="forecast-section animate-fade-in">
                        <h3 class="forecast-title">5-Day Forecast (Simulated)</h3>
                        <div class="forecast-container">
                            ${generateOfflineForecast(cityData.temperature)}
                        </div>
                    </section>
                    
                </div>
            </div>
        </section>
    `;

    // Replace initial state or error section with weather display
    const initialState = document.querySelector('.initial-state');
    const errorSection = document.querySelector('.error-section');
    const existingWeatherDisplay = document.querySelector('.weather-display');

    if (initialState) {
        initialState.outerHTML = weatherHTML;
    } else if (errorSection) {
        errorSection.insertAdjacentHTML('afterend', weatherHTML);
    } else if (existingWeatherDisplay) {
        existingWeatherDisplay.outerHTML = weatherHTML;
    } else {
        const searchSection = document.querySelector('.search-section');
        searchSection.insertAdjacentHTML('afterend', weatherHTML);
    }

    // Animate cards
    setTimeout(() => {
        animateWeatherCards();
    }, 100);
}

/**
 * Show error message for offline mode
 */
function showOfflineError(cityName, availableCities) {
    const errorHTML = `
        <section class="error-section mb-4">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>City not found in offline data:</strong> "${cityName}"<br>
                        <small>Available cities: ${availableCities}</small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </section>
    `;

    const searchSection = document.querySelector('.search-section');
    const existingError = document.querySelector('.error-section');

    if (existingError) {
        existingError.outerHTML = errorHTML;
    } else {
        searchSection.insertAdjacentHTML('afterend', errorHTML);
    }
}

// ============================================================
// TEMPERATURE UNIT CONVERSION
// ============================================================

/**
 * Toggle between Celsius and Fahrenheit
 */
function toggleTemperatureUnit() {
    const tempValue = document.getElementById('tempValue');
    const tempUnit = document.getElementById('tempUnit');
    const feelsLikeValue = document.getElementById('feelsLikeValue');
    const feelsLikeUnit = document.getElementById('feelsLikeUnit');
    const toggleBtn = document.getElementById('toggleUnit');

    if (!tempValue) return; // Exit if no temperature data is displayed

    // Get original Celsius values from data attributes
    const celsiusTemp = parseFloat(tempValue.dataset.celsius);
    const celsiusFeelsLike = parseFloat(feelsLikeValue.dataset.celsius);

    if (isCelsius) {
        // Convert to Fahrenheit
        const fahrenheitTemp = Math.round((celsiusTemp * 9 / 5) + 32);
        const fahrenheitFeelsLike = Math.round((celsiusFeelsLike * 9 / 5) + 32);

        tempValue.textContent = fahrenheitTemp;
        tempUnit.textContent = '°F';
        feelsLikeValue.textContent = fahrenheitFeelsLike;
        feelsLikeUnit.textContent = '°F';
        toggleBtn.innerHTML = '<i class="bi bi-thermometer-half me-1"></i>Switch to °C';

        isCelsius = false;

        // Update forecast temperatures
        const forecastHighs = document.querySelectorAll('.forecast-high .temp-val');
        const forecastLows = document.querySelectorAll('.forecast-low .temp-val');

        forecastHighs.forEach(el => {
            const celsius = parseFloat(el.dataset.celsius);
            el.textContent = Math.round((celsius * 9 / 5) + 32);
        });

        forecastLows.forEach(el => {
            const celsius = parseFloat(el.dataset.celsius);
            el.textContent = Math.round((celsius * 9 / 5) + 32);
        });

    } else {
        // Convert back to Celsius
        tempValue.textContent = celsiusTemp;
        tempUnit.textContent = '°C';
        feelsLikeValue.textContent = celsiusFeelsLike;
        feelsLikeUnit.textContent = '°C';
        toggleBtn.innerHTML = '<i class="bi bi-thermometer-half me-1"></i>Switch to °F';

        isCelsius = true;

        // Update forecast temperatures
        const forecastHighs = document.querySelectorAll('.forecast-high .temp-val');
        const forecastLows = document.querySelectorAll('.forecast-low .temp-val');

        forecastHighs.forEach(el => {
            el.textContent = el.dataset.celsius;
        });

        forecastLows.forEach(el => {
            el.textContent = el.dataset.celsius;
        });
    }

    // Add animation effect
    tempValue.style.transform = 'scale(1.1)';
    setTimeout(() => {
        tempValue.style.transform = 'scale(1)';
    }, 200);
}

/**
 * Generate simulated forecast HTML for offline mode
 */
function generateOfflineForecast(currentTemp) {
    let html = '';
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const today = new Date();

    for (let i = 1; i <= 5; i++) {
        const date = new Date(today);
        date.setDate(today.getDate() + i);

        const dayIndex = date.getDay();
        const dayName = days[dayIndex];
        const month = date.toLocaleString('default', { month: 'short' });
        const dayDate = date.getDate();

        // Simulate temp variation
        const variation = Math.floor(Math.random() * 5) - 2;
        const tempMax = parseInt(currentTemp) + variation;
        const tempMin = tempMax - 8;

        const icons = ['01d', '02d', '03d', '04d', '09d', '10d'];
        const randomIcon = icons[Math.floor(Math.random() * icons.length)];
        const descriptions = ['Clear sky', 'Few clouds', 'Scattered clouds', 'Broken clouds', 'Shower rain', 'Rain'];
        const description = descriptions[icons.indexOf(randomIcon)];

        html += `
            <div class="forecast-card">
                <div class="forecast-day">${dayName}</div>
                <div class="forecast-date text-muted small">${month} ${dayDate}</div>
                <img 
                    src="https://openweathermap.org/img/wn/${randomIcon}@2x.png" 
                    alt="${description}"
                    class="forecast-icon"
                >
                <div class="forecast-description small text-muted mb-2">${description}</div>
                <div class="forecast-temp">
                    <span class="forecast-high">
                        <span class="temp-val" data-celsius="${tempMax}">${tempMax}</span>°
                    </span>
                    <span class="forecast-low">
                        <span class="temp-val" data-celsius="${tempMin}">${tempMin}</span>°
                    </span>
                </div>
            </div>
        `;
    }

    return html;
}

// ============================================================
// FORM VALIDATION
// ============================================================

/**
 * Initialize form validation on page load
 */
document.addEventListener('DOMContentLoaded', function () {

    // Bootstrap form validation
    const form = document.getElementById('weatherForm');

    if (form) {
        form.addEventListener('submit', function (event) {
            const cityInput = document.getElementById('cityInput');

            // Check if input is empty
            if (!cityInput.value.trim()) {
                event.preventDefault();
                event.stopPropagation();
                cityInput.classList.add('is-invalid');
                cityInput.focus();
            } else {
                cityInput.classList.remove('is-invalid');
                cityInput.classList.add('is-valid');

                // Check if we're in offline mode
                if (isOfflineMode) {
                    event.preventDefault(); // Stop PHP form submission
                    searchOfflineWeather(cityInput.value.trim());
                    return false;
                }

                // Show loading state for API mode
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
                }
            }

            form.classList.add('was-validated');
        }, false);

        // Remove validation on input
        const cityInput = document.getElementById('cityInput');
        if (cityInput) {
            cityInput.addEventListener('input', function () {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }

                // CRITICAL: Clear hidden coordinates if user starts typing manually
                const latIn = document.getElementById('latInput');
                const lonIn = document.getElementById('lonInput');
                if (latIn) latIn.value = '';
                if (lonIn) lonIn.value = '';
            });
        }
    }

    // Auto-focus on city input field
    const cityInput = document.getElementById('cityInput');
    if (cityInput && !cityInput.value) {
        cityInput.focus();
    }

    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';

    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// ============================================================
// KEYBOARD SHORTCUTS
// ============================================================

/**
 * Add keyboard shortcuts for better UX
 */
document.addEventListener('keydown', function (event) {
    // Press 'T' to toggle temperature unit (when not typing in input)
    if (event.key === 't' || event.key === 'T') {
        const cityInput = document.getElementById('cityInput');
        if (document.activeElement !== cityInput) {
            const toggleBtn = document.getElementById('toggleUnit');
            if (toggleBtn) {
                toggleTemperatureUnit();
            }
        }
    }

    // Press '/' to focus search input
    if (event.key === '/') {
        event.preventDefault();
        const cityInput = document.getElementById('cityInput');
        if (cityInput) {
            cityInput.focus();
            cityInput.select();
        }
    }
});

// ============================================================
// LOADING ANIMATIONS
// ============================================================

/**
 * Add entrance animations to weather cards
 */
function animateWeatherCards() {
    const cards = document.querySelectorAll('.detail-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Run animations if weather data is displayed
if (document.querySelector('.weather-display')) {
    animateWeatherCards();
}

// ============================================================
// AUTO-DISMISS ALERTS
// ============================================================

/**
 * Auto-dismiss error alerts after 8 seconds
 */
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 8000);
    });
});

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Get current timestamp
 */
function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString();
}

/**
 * Log message to console (for debugging)
 */
function logDebug(message) {
    if (console && console.log) {
        console.log(`[Weather App] ${message}`);
    }
}

// ============================================================
// INITIALIZATION MESSAGE
// ============================================================

console.log('%c🌤️ Weather Info Display Loaded Successfully!', 'color: #667eea; font-size: 16px; font-weight: bold;');
console.log('%cKeyboard Shortcuts:', 'color: #764ba2; font-weight: bold;');
console.log('  • Press "/" to focus search');
console.log('  • Press "T" to toggle temperature unit');
console.log('%cFeatures:', 'color: #764ba2; font-weight: bold;');
console.log('  • Light/Dark Mode Toggle');
console.log('  • API/Offline Mode Support');
console.log('  • Geolocation Detection');
console.log('  • Popular City Quick Search');

// ============================================================
// AUTO-INITIALIZE ON PAGE LOAD
// ============================================================

// Load saved theme when page loads
document.addEventListener('DOMContentLoaded', function () {
    loadSavedTheme();

    // Detect weather condition from PHP data if exists
    const conditionText = document.querySelector('.condition-text');
    if (conditionText) {
        setWeatherBackground(conditionText.textContent);
    }

    logDebug('Weather App Initialized');
});


// ============================================================
// DYNAMIC SEARCH SUGGESTIONS (API-BASED)
// ============================================================

const searchInput = document.getElementById('cityInput');
const suggestionsBox = document.getElementById('suggestionsBox');
let debounceTimer;

if (searchInput && suggestionsBox) {
    // Input Event: Fetch suggestions as user types
    searchInput.addEventListener('input', function () {
        const query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 3) {
            suggestionsBox.classList.add('d-none');
            suggestionsBox.innerHTML = '';
            return;
        }

        // Debounce API calls (300ms delay)
        debounceTimer = setTimeout(() => {
            fetchCitySuggestions(query);
        }, 300);
    });

    // UX: Close suggestions when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('d-none');
        }
    });

    // UX: Show suggestions again on focus if input has value
    searchInput.addEventListener('focus', function () {
        if (this.value.trim().length >= 3 && suggestionsBox.children.length > 0) {
            suggestionsBox.classList.remove('d-none');
        }
    });
}

/**
 * Fetch matching cities from OpenWeatherMap Geocoding API
 */
async function fetchCitySuggestions(query) {
    if (typeof WEATHER_API_KEY === 'undefined' || !WEATHER_API_KEY) return;

    try {
        const response = await fetch(`https://api.openweathermap.org/geo/1.0/direct?q=${query}&limit=5&appid=${WEATHER_API_KEY}`);
        if (!response.ok) return;

        const data = await response.json();
        renderSuggestions(data);
    } catch (error) {
        console.error('Error fetching suggestions:', error);
    }
}

/**
 * Render usage suggestions list
 */
function renderSuggestions(cities) {
    if (!cities || cities.length === 0) {
        suggestionsBox.classList.add('d-none');
        return;
    }

    // Remove duplicates based on unique string
    const uniqueCities = [];
    const seen = new Set();

    cities.forEach(city => {
        const state = city.state ? `, ${city.state}` : '';
        const country = city.country ? ` (${city.country})` : '';
        const id = `${city.name}${state}${country}`;

        if (!seen.has(id)) {
            seen.add(id);
            uniqueCities.push({ ...city, displayName: id });
        }
    });

    const html = uniqueCities.map(city => {
        return `
            <div class="suggestion-item" onclick="selectCity('${city.name}')">
                <i class="bi bi-geo-alt"></i>
                <span>${city.displayName}</span>
            </div>
        `;
    }).join('');

    suggestionsBox.innerHTML = html;
    suggestionsBox.classList.remove('d-none');
}

/**
 * Handle city selection from suggestions
 */
function selectCity(cityName) {
    const input = document.getElementById('cityInput');
    if (input) {
        input.value = cityName;
        // Hide suggestions
        const box = document.getElementById('suggestionsBox');
        if (box) box.classList.add('d-none');

        // Use the existing quick search function to submit
        // trigger loading state UI
        const form = document.getElementById('weatherForm');
        if (form) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                btn.disabled = true;
            }
            form.submit();
        }
    }
}
