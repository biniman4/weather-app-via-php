# Weather app

Weather app is a practical web application built with PHP that provides real-time weather information for any city in the world. It uses the OpenWeatherMap API to deliver accurate data and features a modern, responsive design that works smoothly across all devices.

## Features

- **Global Search**: Find current weather for any city across the globe.
- **Detailed Information**: View temperature, conditions, humidity, wind speed, and pressure.
- **Smart Design**: Uses glassmorphism and smooth animations for a premium look.
- **Responsive Layout**: Designed to look great on phones, tablets, and desktops.
- **Unit Switching**: Easily toggle between Celsius and Fahrenheit.
- **Geolocation**: Get instant weather for your current location.

## Quick Start

### Prerequisites
- A local server like XAMPP or WAMP.
- PHP 7.4 or newer.
- An internet connection.
- An OpenWeatherMap API key (you can get one for free on their website).

### Installation
1. Download the project and place the `weather app` folder in your server's root directory (e.g., `C:\xampp\htdocs\weather app\`).
2. Open `config.php` and paste your API key in the `API_KEY` section.
3. Start your Apache server in the XAMPP Control Panel.
4. Open your browser and go to `http://localhost/weather app/`.

## Deployment

This project is configured for easy deployment on Vercel.

1. Push your code to a GitHub repository.
2. Link the repository to your Vercel account.
3. In the Vercel Project Settings, add an environment variable called `WEATHER_API_KEY` and paste your OpenWeatherMap key.
4. Deploy, and Vercel will handle the rest via the included `vercel.json` configuration.

## How it Works

The application follows a simple but effective structure:

- **index.php**: The main file that handles the user interface and coordinates with the PHP logic to fetch data.
- **config.php**: Stores configuration settings and manages the API credentials securely.
- **assets/**: Contains all the custom styling (CSS), interactive scripts (JS), and images for the app.

The backend uses PHP's cURL extension to communicate with the OpenWeatherMap API. Once the data is retrieved, it's parsed from JSON into the user-friendly format you see on the dashboard.

## Usage and Shortcuts

- **Search**: Type any city name and hit Enter or click search.
- **Unit Toggle**: Click the switch in the header to change between Celsius and Fahrenheit.
- **Location**: Use the "Get My Location" button to automatically find weather for your current spot.
- **Quick Focus**: Press the "/" key anytime to jump straight to the search bar.

## Troubleshooting

- **City not found**: Double-check the spelling or try a more well-known version of the city name.
- **No data appearing**: Check your internet connection and ensure your API key in `config.php` is correct.
- **CSS not loading**: If deployed on Vercel, ensure the `assets` folder is correctly routed in your `vercel.json`.

---
*Built for the Web Design and Programming course.*
