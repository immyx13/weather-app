<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Set content type to JSON

// Connect to the database
$mysqli = new mysqli("localhost", "2383892", "5c4erz", "db2383892");
if ($mysqli->connect_errno) {
    echo json_encode(['error' => 'Database connection failed', 'details' => $mysqli->connect_error]);
    exit();
}

// Check for city parameter
if (!isset($_GET['city']) || empty($_GET['city'])) {
    echo json_encode(['error' => 'City parameter is missing']);
    exit();
}

$city = $_GET['city'];

// Execute SQL query to retrieve recent weather data
$sql = "SELECT * FROM weather WHERE city = ? AND weather_when >= DATE_SUB(NOW(), INTERVAL 10 SECOND) ORDER BY weather_when DESC LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $city);
$stmt->execute();
$result = $stmt->get_result();

// Check if data is present in the database
if ($result->num_rows > 0) {
    // Fetch and output the data as JSON
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    // If no recent data is found, fetch fresh data from OpenWeather API
    $apiKey = '3e96fb0cdaca257f83229f369d98f41f';
    $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&appid=' . $apiKey . '&units=metric';
    
    $data = file_get_contents($url);
    if ($data === false) {
        echo json_encode(['error' => 'Failed to connect to OpenWeather API']);
        exit();
    }
    
    // Decode JSON response from API
    $json = json_decode($data, true);
    if ($json === null) {
        echo json_encode(['error' => 'Error decoding JSON from API']);
        exit();
    }

    // Check for required fields in API response
    if (!isset($json['weather'][0]['description']) || !isset($json['main']['temp']) || !isset($json['wind']['speed'])) {
        echo json_encode(['error' => 'Incomplete data from OpenWeather API', 'response' => $json]);
        exit();
    }

    // Extract required fields
    $description = $json['weather'][0]['description'];
    $temp = $json['main']['temp'];
    $windSpeed = $json['wind']['speed'];

    // Insert new data into the database
    $insertStmt = $mysqli->prepare("INSERT INTO weather (city, weather_description, weather_temperature, weather_wind, weather_when) VALUES (?, ?, ?, ?, NOW())");
    $insertStmt->bind_param("ssdd", $city, $description, $temp, $windSpeed);
    
    if (!$insertStmt->execute()) {
        echo json_encode(['error' => 'Failed to insert data into database', 'sql_error' => $insertStmt->error]);
        exit();
    }

    // Output the new data as JSON
    echo json_encode([
        'city' => $city,
        'weather_description' => $description,
        'weather_temperature' => $temp,
        'weather_wind' => $windSpeed,
        'weather_when' => date('Y-m-d H:i:s')
    ]);

    // Close insert statement
    $insertStmt->close();
}

// Free result set and close database connection
$stmt->close();
$mysqli->close();
?>
