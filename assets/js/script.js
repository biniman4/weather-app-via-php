/**
 * JavaScript for the Weather App.
 * Handles theme toggling, geolocation, temperature conversion, and UI animations.
 */

let isCelsius = true;
let isOfflineMode = false;
let staticWeatherData = null;
let currentWeatherCondition = 'clear';

/**
 * Toggles between light and dark themes.
 */
function toggleTheme() {
    const currentTheme = document.body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

/**
 * Loads the user's saved theme preference from local storage.
 */
function loadSavedTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);

    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.checked = savedTheme === 'dark';
    }
}

/**
 * Sets the body background style based on the weather condition.
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

/**
 * Requests the user's current location to fetch weather data.
 */
function getLocation() {
    const geoBtn = document.getElementById('geoBtn');

    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    geoBtn.disabled = true;
    geoBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Locating...';

    // Detailed debug logging for accuracy and timing.
    console.log('[Geolocation] Starting location request...');

    navigator.geolocation.getCurrentPosition(
        async function (position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            const accuracy = Math.round(position.coords.accuracy);

            console.log(`[Geolocation] Found: ${lat}, ${lon} (Accuracy: ${accuracy}m)`);

            // Store accuracy for display after page reload.
            sessionStorage.setItem('lastAccuracy', accuracy);

            try {
                const latInput = document.getElementById('latInput');
                const lonInput = document.getElementById('lonInput');
                const cityInput = document.getElementById('cityInput');

                if (latInput && lonInput) {
                    if (accuracy > 10000) {
                        // Use hardcoded Haramaya coordinates for low-accuracy results (likely ISP center)
                        latInput.value = 9.3947;
                        lonInput.value = 42.0131;
                        if (cityInput) cityInput.value = "Haramaya (Ale Maya)";
                    } else {
                        latInput.value = lat;
                        lonInput.value = lon;
                        if (cityInput) {
                            if (accuracy > 2000) {
                                cityInput.value = "Haramaya Area";
                            } else {
                                cityInput.value = "Current Location";
                            }
                        }
                    }
                    document.getElementById('weatherForm').submit();
                } else {
                    throw new Error('Coordinates inputs not found.');
                }
            } catch (error) {
                console.error('[Geolocation] Submission error:', error);
                quickSearch('Haramaya');
            } finally {
                geoBtn.disabled = false;
                geoBtn.innerHTML = '<i class="bi bi-geo-alt-fill me-2"></i>Use My Location';
            }
        },
        function (error) {
            let message = '';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location access denied. Falling back to Haramaya...';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information is unavailable. Falling back to Haramaya...';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out. Falling back to Haramaya...';
                    break;
                default:
                    message = 'An unknown error occurred. Falling back to Haramaya...';
            }
            console.error('[Geolocation] Error:', error.message, error.code);
            alert(message);
            quickSearch('Haramaya');
            geoBtn.disabled = false;
            geoBtn.innerHTML = '<i class="bi bi-geo-alt-fill me-2"></i>Use My Location';
        },
        {
            timeout: 15000,          // 15 seconds to allow for GPS fix
            enableHighAccuracy: true, // Request best possible accuracy
            maximumAge: 0            // Force a fresh location (don't use cache)
        }
    );
}

/**
 * Performs a search for a predefined city name.
 */
function quickSearch(cityName) {
    const cityInput = document.getElementById('cityInput');
    cityInput.value = cityName;

    if (isOfflineMode) {
        searchOfflineWeather(cityName);
    } else {
        document.getElementById('weatherForm').submit();
    }
}

/**
 * Syncs the global unit state with the header toggle.
 */
function toggleUnitFromHeader() {
    const unitToggle = document.getElementById('unitToggle');
    // The toggle state should drive the isCelsius state directly.
    // unitToggle.checked = true means Fahrenheit, so isCelsius should be false.
    toggleTemperatureUnit(!unitToggle.checked);
}

/**
 * Toggles between API-connected and Static (Offline) data modes.
 */
function toggleMode() {
    const checkbox = document.getElementById('offlineMode');
    const modeStatus = document.getElementById('modeStatus');
    isOfflineMode = checkbox.checked;

    if (isOfflineMode) {
        modeStatus.innerHTML = '<i class="bi bi-wifi-off me-1"></i> Offline Mode';
        loadStaticWeatherData();
    } else {
        modeStatus.innerHTML = '<i class="bi bi-wifi me-1"></i> API Mode';
    }
}

/**
 * Loads sample weather data for the offline demo.
 */
async function loadStaticWeatherData() {
    try {
        const response = await fetch(`assets/data/sample-weather.json?v=${new Date().getTime()}`);
        const data = await response.json();
        staticWeatherData = data.cities;
    } catch (error) {
        console.error('Offline Data Error:', error);
    }
}

/**
 * Searches the static dataset for weather information.
 */
function searchOfflineWeather(cityName) {
    if (!staticWeatherData) {
        alert('Offline data is still loading...');
        return null;
    }

    const city = staticWeatherData.find(c => c.name.toLowerCase() === cityName.toLowerCase());

    if (city) {
        displayOfflineWeather(city);
        return city;
    } else {
        alert(`City not found in offline data. Try: ${staticWeatherData.map(c => c.name).join(', ')}`);
        return null;
    }
}

/**
 * Renders offline weather data to the UI.
 */
function displayOfflineWeather(cityData) {
    const weatherHTML = `
        <section class="weather-display animate-fade-in">
            <div class="weather-dashboard-card">
                <div class="alert alert-info py-2 small mb-3">Offline Mode: Using demo data</div>
                <div class="weather-header">
                    <div class="location-title">${cityData.name}, ${cityData.country}</div>
                    <div class="update-time">Demo Data</div>
                </div>
                <div class="weather-main-row">
                    <div class="weather-left-col">
                        <img src="https://openweathermap.org/img/wn/${cityData.icon}@4x.png" class="main-icon animate-float">
                        <div class="main-temp-group">
                            <div class="main-temp">
                                <span id="tempValue" data-celsius="${cityData.temperature}">${cityData.temperature}</span>
                                <span class="h1 align-top fw-normal text-secondary" id="tempUnit">°C</span>
                            </div>
                            <div class="main-desc">${cityData.condition}</div>
                            <button class="d-none" id="toggleUnit" onclick="toggleTemperatureUnit()"></button>
                        </div>
                    </div>
                </div>
                <div class="stats-grid-compact mt-4">
                    <div class="stat-item"><span class="stat-label">Humidity</span><span class="stat-value">${cityData.humidity}%</span></div>
                    <div class="stat-item"><span class="stat-label">Wind</span><span class="stat-value">${cityData.wind_speed} km/h</span></div>
                </div>
            </div>
        </section>`;

    const display = document.querySelector('.weather-display') || document.querySelector('.search-container');
    display.outerHTML = weatherHTML;
    animateWeatherCards();
}

/**
 * Toggles displayed temperatures between Celsius and Fahrenheit.
 * @param {boolean|null} forceCelsius - Optional override to set a specific unit.
 */
function toggleTemperatureUnit(forceCelsius = null) {
    const tempValue = document.getElementById('tempValue');
    const tempUnit = document.getElementById('tempUnit');
    const feelsLikeValue = document.getElementById('feelsLikeValue');
    const feelsLikeUnit = document.getElementById('feelsLikeUnit');

    if (!tempValue) return;

    // Determine the target state.
    const targetIsCelsius = forceCelsius !== null ? forceCelsius : !isCelsius;

    // Update the global state.
    isCelsius = targetIsCelsius;

    const celsiusTemp = parseFloat(tempValue.dataset.celsius);
    const celsiusFeelsLike = parseFloat(feelsLikeValue?.dataset.celsius || 0);

    if (!isCelsius) {
        // Converting to Fahrenheit
        const fTemp = Math.round((celsiusTemp * 9 / 5) + 32);
        tempValue.textContent = fTemp;
        tempUnit.textContent = '°F';
        if (feelsLikeValue) {
            feelsLikeValue.textContent = Math.round((celsiusFeelsLike * 9 / 5) + 32);
            feelsLikeUnit.textContent = '°F';
        }

        document.querySelectorAll('.temp-val').forEach(el => {
            if (el.dataset.celsius) {
                el.textContent = Math.round((parseFloat(el.dataset.celsius) * 9 / 5) + 32);
            }
        });
    } else {
        // Switching/Staying at Celsius
        tempValue.textContent = Math.round(celsiusTemp);
        tempUnit.textContent = '°C';
        if (feelsLikeValue) {
            feelsLikeValue.textContent = Math.round(celsiusFeelsLike);
            feelsLikeUnit.textContent = '°C';
        }

        document.querySelectorAll('.temp-val').forEach(el => {
            if (el.dataset.celsius) {
                el.textContent = el.dataset.celsius;
            }
        });
    }

    // Sync header toggle state.
    const unitToggle = document.getElementById('unitToggle');
    if (unitToggle) unitToggle.checked = !isCelsius;
}

/**
 * Form and keyboard initialization.
 */
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('weatherForm');
    const cityInput = document.getElementById('cityInput');

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!cityInput.value.trim()) {
                event.preventDefault();
                cityInput.classList.add('is-invalid');
            } else if (isOfflineMode) {
                event.preventDefault();
                searchOfflineWeather(cityInput.value.trim());
            } else {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) btn.disabled = true;
            }
        });

        cityInput.addEventListener('input', function () {
            const latIn = document.getElementById('latInput');
            const lonIn = document.getElementById('lonInput');
            if (latIn) latIn.value = '';
            if (lonIn) lonIn.value = '';
        });
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === '/') {
            e.preventDefault();
            cityInput?.focus();
        }
        if ((e.key === 't' || e.key === 'T') && document.activeElement !== cityInput) {
            toggleTemperatureUnit();
        }
    });

    loadSavedTheme();

    // Initialize units based on the toggle state.
    const unitToggle = document.getElementById('unitToggle');
    if (unitToggle) {
        isCelsius = !unitToggle.checked;
        if (document.getElementById('tempValue')) {
            toggleTemperatureUnit(isCelsius);
        }
    }

    // Auto-detect location if no weather data is present and no error is shown.
    if (typeof HAS_WEATHER_DATA !== 'undefined' && !HAS_WEATHER_DATA && !HAS_ERROR) {
        // Delay slightly for better UX (let initial animations run)
        setTimeout(() => {
            console.log('[Geolocation] Auto-detecting location on startup...');
            getLocation();
        }, 800);
    } else if (HAS_WEATHER_DATA) {
        // Display preserved accuracy if available.
        const accuracy = sessionStorage.getItem('lastAccuracy');
        const accuracyLabel = document.getElementById('accuracyLabel');
        if (accuracy && accuracyLabel) {
            accuracyLabel.textContent = `Accuracy: ±${accuracy}m`;
            accuracyLabel.classList.remove('d-none');
            // Check for potential inaccuracy warning.
            if (parseInt(accuracy) > 1000) {
                accuracyLabel.classList.replace('bg-light', 'bg-warning');
                accuracyLabel.classList.replace('text-dark', 'text-dark');
            }
            sessionStorage.removeItem('lastAccuracy');
        }
    }
});

/**
 * Handles subtle entrance animations for data cards.
 */
function animateWeatherCards() {
    document.querySelectorAll('.stat-item').forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';
        setTimeout(() => {
            card.style.transition = 'all 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, i * 50);
    });
}

/**
 * Autocomplete logic for the search input.
 */
const suggestionsBox = document.getElementById('suggestionsBox');
let debounceTimer;

if (cityInput && suggestionsBox) {
    cityInput.addEventListener('input', function () {
        const query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 3) {
            suggestionsBox.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const url = `http://api.openweathermap.org/geo/1.0/direct?q=${encodeURIComponent(query)}&limit=5&appid=${WEATHER_API_KEY}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.length > 0) {
                    suggestionsBox.innerHTML = data.map(city => `
                        <div class="suggestion-item" onclick="selectSuggestion('${city.name}', '${city.country}')">
                            <strong>${city.name}</strong>, ${city.country}
                        </div>`).join('');
                    suggestionsBox.classList.remove('d-none');
                } else {
                    suggestionsBox.classList.add('d-none');
                }
            } catch (error) {
                console.error('Suggestions error:', error);
            }
        }, 300);
    });
}

function selectSuggestion(name, country) {
    const cityInput = document.getElementById('cityInput');
    cityInput.value = name;
    suggestionsBox.classList.add('d-none');
    document.getElementById('weatherForm').submit();
}

// Close suggestions when clicking outside.
document.addEventListener('click', (e) => {
    if (!suggestionsBox?.contains(e.target) && e.target !== cityInput) {
        suggestionsBox?.classList.add('d-none');
    }
});
