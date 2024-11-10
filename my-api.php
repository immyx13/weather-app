<?php
// Enable error reporting to detect issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check for city parameter
if (!isset($_GET['city']) || empty($_GET['city'])) {
    echo json_encode(['error' => 'City parameter is missing']);
    exit;
}

// API URL with city parameter
$url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($_GET['city']) . '&appid=3e96fb0cdaca257f83229f369d98f41f&units=metric';

// Fetch data from OpenWeather API
$data = file_get_contents($url);
if ($data === false) {
    echo json_encode(['error' => 'Failed to connect to OpenWeather API']);
    exit;
}

// Decode the JSON data
$json = json_decode($data, true);
if ($json === null) {
    echo json_encode(['error' => 'Error decoding JSON from API']);
    exit;
}

// Check if required fields are present in the API response
if (!isset($json['weather'][0]['description']) || !isset($json['main']['temp']) || 
    !isset($json['wind']['speed']) || !isset($json['weather'][0]['icon'])) {
    echo json_encode(['error' => 'Incomplete data from OpenWeather API', 'response' => $json]);
    exit;
}

// Prepare response with required fields
$response = [
    'weather_description' => $json['weather'][0]['description'],
    'weather_temperature' => $json['main']['temp'],
    'weather_wind' => $json['wind']['speed'],
    'weather_when' => date("Y-m-d H:i:s"), // Add current timestamp
    'weather_icon' => $json['weather'][0]['icon'] // Add icon code
];

// Output the response as JSON
echo json_encode($response);
?>
