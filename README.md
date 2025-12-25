# 🌤️ Dynamic Weather Info Display

A complete, production-ready PHP web application that fetches and displays real-time weather data using the OpenWeatherMap API. Built with PHP, Bootstrap 5, and modern web technologies for a Web Design and Programming course assignment.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)

## 📋 Features

### ✅ Core Functionality
- **🔍 City Search**: Search weather information for any city worldwide
- **🌡️ Real-Time Weather Data**: Display current temperature, weather conditions, humidity, wind speed, and atmospheric pressure
- **🌈 Dynamic Weather Icons**: Automatically updated based on current conditions
- **♨️ Temperature Unit Toggle**: Switch between Celsius (°C) and Fahrenheit (°F)
- **❌ Comprehensive Error Handling**: User-friendly messages for invalid cities, API failures, and network issues
- **📱 Fully Responsive Design**: Perfect display on mobile, tablet, and desktop devices

### 🎨 Premium Design
- **Modern glassmorphism effects** with backdrop blur
- **Vibrant gradient backgrounds** with animated elements
- **Smooth animations and transitions** for enhanced UX
- **Mobile-first responsive layout** using Bootstrap 5 grid system
- **Clean, professional UI** with Inter font family
- **Interactive hover effects** and micro-animations

### 🛠️ Technical Highlights
- **Server-side PHP** for API integration using cURL
- **JSON data parsing** for weather information
- **Bootstrap 5 components** (forms, cards, alerts, buttons)
- **Client-side JavaScript** for dynamic interactions
- **Form validation** with user feedback
- **Semantic HTML5** structure
- **SEO-optimized** with proper meta tags

## 🚀 Getting Started

### Prerequisites

- **XAMPP** (recommended) or **WAMP** server
- **PHP 7.4 or higher**
- **Internet connection** (for API requests)
- **OpenWeatherMap API key** (free)

### Installation Steps

#### 1. Download/Clone the Project

Download this project and place the entire `weather app` folder in your XAMPP `htdocs` directory:

```
c:\xampp\htdocs\weather app\
```

#### 2. Get Your Free API Key

1. Visit [OpenWeatherMap](https://openweathermap.org/api)
2. Sign up for a free account
3. Navigate to "API keys" section
4. Copy your API key

#### 3. Configure API Key

Open the `config.php` file and replace `YOUR_API_KEY_HERE` with your actual API key:

```php
// In config.php
define('API_KEY', 'paste_your_api_key_here');
```

#### 4. Start XAMPP

1. Open **XAMPP Control Panel**
2. Start **Apache** server
3. Ensure Apache is running (green indicator)

#### 5. Access the Application

Open your web browser and navigate to:

```
http://localhost/weather app/
```

Or if you have a custom port configuration:

```
http://localhost:8080/weather app/
```

## 🌐 Deploying to Vercel

This project is optimized for deployment on [Vercel](https://vercel.com) using a PHP runtime bridge.

### Deployment steps:

1.  **Push your project to GitHub** (or GitLab/Bitbucket).
2.  **Import to Vercel**: Connect your repository to Vercel.
3.  **Configure Environment Variables** (CRITICAL):
    -   Go to **Project Settings** > **Environment Variables**.
    -   Add a new variable: `WEATHER_API_KEY`
    -   Paste your OpenWeatherMap API Key as the value.
4.  **Deploy**: Vercel will automatically detect `vercel.json` and set up the PHP environment.

The `vercel.json` file handles the routing and PHP runtime configuration automatically.

## 📖 Usage Guide

### Searching for Weather

1. **Enter a city name** in the search field (e.g., "London", "New York", "Tokyo")
2. Click the **Search** button or press **Enter**
3. View real-time weather information displayed on the screen

### Temperature Unit Toggle

- Click the **"Switch to °F"** button to convert temperatures to Fahrenheit
- Click **"Switch to °C"** to convert back to Celsius
- Or press the **"T"** key on your keyboard for quick toggle

### Keyboard Shortcuts

- **"/"** - Focus on the search input field
- **"T"** - Toggle temperature unit (°C ↔ °F)

## 📁 Project Structure

```
weather app/
│
├── index.php              # Main application file (UI + PHP logic)
├── config.php             # API configuration and settings
├── README.md              # This documentation file
│
└── assets/
    ├── css/
    │   └── style.css      # Custom styles and animations
    ├── js/
    │   └── script.js      # Client-side JavaScript
    └── images/            # Additional images (if needed)
```

## 🔧 Technical Documentation

### PHP Architecture

**File: `config.php`**
- Stores API key and configuration constants
- Defines API endpoint URL
- Sets default temperature unit

**File: `index.php`**
- **PHP Section** (Top):
  - Handles form submission via POST
  - Validates city input
  - Makes cURL request to OpenWeatherMap API
  - Parses JSON response
  - Implements error handling
  
- **HTML Section**:
  - Bootstrap 5 responsive layout
  - Search form with validation
  - Dynamic weather display cards
  - Error message alerts
  - Semantic HTML structure

### API Integration

**Method**: cURL (Recommended for robust requests)

**Endpoint**: `https://api.openweathermap.org/data/2.5/weather`

**Parameters**:
- `q`: City name
- `appid`: Your API key
- `units`: Measurement system (metric/imperial)

**Response Format**: JSON

### Error Handling

The application handles:
- ❌ Empty city input
- ❌ Invalid city names (404)
- ❌ Invalid API key (401)
- ❌ API rate limits (429)
- ❌ Network connectivity issues
- ❌ cURL errors

### CSS Framework

**Bootstrap 5.3** via CDN
- Grid system for responsive layout
- Form controls and validation
- Card components
- Alert messages
- Button styles
- Utility classes

**Custom CSS**:
- Glassmorphism effects
- Gradient backgrounds
- Smooth animations
- Responsive breakpoints
- Premium design elements

### JavaScript Features

**Temperature Conversion**:
```javascript
°F = (°C × 9/5) + 32
```

**Form Validation**:
- Real-time input validation
- Bootstrap validation states
- Loading indicators

**User Experience**:
- Keyboard shortcuts
- Auto-focus on input
- Smooth animations
- Auto-dismiss alerts

## 🧪 Testing

### Test Cases

1. **Valid City Search**
   - Search: "London"
   - Expected: Display weather data for London

2. **Invalid City**
   - Search: "InvalidCityXYZ123"
   - Expected: Error alert "City not found"

3. **Empty Input**
   - Search: (leave empty)
   - Expected: Validation error message

4. **Temperature Toggle**
   - Action: Click toggle button
   - Expected: Temperature converts between °C and °F

5. **Responsive Design**
   - Action: Resize browser window
   - Expected: Layout adapts to screen size

### Browser Compatibility

Tested and working on:
- ✅ Google Chrome (Recommended)
- ✅ Mozilla Firefox
- ✅ Microsoft Edge
- ✅ Safari

## 🐛 Troubleshooting

### "API key not configured" error
**Solution**: Open `config.php` and add your OpenWeatherMap API key

### "City not found" error
**Solution**: 
- Check spelling of city name
- Try major cities first (e.g., "Paris", "Tokyo")
- Use English city names

### Page not loading
**Solution**:
- Ensure Apache is running in XAMPP
- Check if the URL is correct: `http://localhost/weather app/`
- Verify files are in `c:\xampp\htdocs\weather app\`

### No weather data displayed
**Solution**:
- Check your internet connection
- Verify API key is valid
- Check OpenWeatherMap API status

### cURL errors
**Solution**:
- Ensure cURL is enabled in PHP (php.ini)
- Check firewall settings
- Verify internet connectivity

## 📚 API Documentation

**OpenWeatherMap API**
- Documentation: https://openweathermap.org/api
- Current Weather Data: https://openweathermap.org/current
- API Keys: https://home.openweathermap.org/api_keys

## 🎓 Academic Context

This project is designed for a **Web Design and Programming** course assignment and demonstrates:

- ✅ Server-side programming with PHP
- ✅ RESTful API integration
- ✅ JSON data parsing
- ✅ Responsive web design with Bootstrap
- ✅ Client-side JavaScript interactions
- ✅ Form validation and error handling
- ✅ Modern CSS techniques
- ✅ User experience best practices

## 📝 Code Quality

- **Clean, readable code** with comprehensive comments
- **Proper separation of concerns** (PHP, HTML, CSS, JS)
- **Semantic HTML5** structure
- **Bootstrap best practices** implementation
- **Error handling** at every level
- **Security considerations** (input sanitization)

## 🔐 Security Notes

- User input is sanitized using `trim()` and `htmlspecialchars()`
- API key stored in separate configuration file
- cURL SSL verification (can be enabled for production)
- No sensitive data exposed in frontend

## 📄 License

This is an academic project created for educational purposes.

## 👨‍💻 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the OpenWeatherMap API documentation
3. Verify all installation steps are completed correctly

## 🌟 Features Showcase

### Temperature Display
- Large, readable temperature values
- Gradient text effects
- "Feels like" temperature
- Unit toggle functionality

### Weather Details
- Humidity percentage
- Wind speed (km/h)
- Atmospheric pressure (hPa)
- Weather condition description

### Visual Design
- Animated weather icons
- Glassmorphism card effects
- Gradient backgrounds
- Smooth transitions
- Responsive layout

---

**Built with ❤️ using PHP, Bootstrap 5, and OpenWeatherMap API**

*Last Updated: December 2025*
