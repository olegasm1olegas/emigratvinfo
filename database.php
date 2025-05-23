<?php
// --- START Geolocation Restriction ---
function isUserFromRimini($ipAddress) {
    // Placeholder for actual geolocation lookup.
    // In a real implementation, you would use a service or database (e.g., GeoIP2, ip-api.com)
    // For demonstration, we'll use a few dummy IPs. Replace with real IPs for testing if needed.
    $riminiTestIPs = [
        '1.2.3.4', // Example IP 1 (Rimini)
        '5.6.7.8', // Example IP 2 (Rimini)
        // Add more test IPs if desired
    ];

    // For more robust simulation, you could use a free API for your own testing IP if this script is hosted.
    // Example (use with caution, rate limits apply):
    // $details = @json_decode(@file_get_contents("http://ip-api.com/json/{$ipAddress}"));
    // if ($details && $details->status == 'success') {
    //     // Example check for city and country (adjust based on actual API response structure)
    //     if (strtolower($details->city) == 'rimini' && strtolower($details->countryCode) == 'it') {
    //         return true;
    //     }
    // }

    if (in_array($ipAddress, $riminiTestIPs)) {
        return true; // This IP is on our test list for Rimini
    }

    return false; // Default: IP is not from Rimini or not on test list
}

// Get user's IP address
// Be mindful of proxies. HTTP_X_FORWARDED_FOR can be spoofed.
// REMOTE_ADDR is more reliable but might show proxy IP.
// For simplicity, we use REMOTE_ADDR. A production system needs a robust IP detection strategy.
$userIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; // Default to a safe IP if not set

if (isUserFromRimini($userIP)) {
    // Log the attempt (optional)
    error_log("Access attempt from Rimini blocked for IP: " . $userIP);
    
    // Block access
    http_response_code(403); // Forbidden
    die("Access from your current location is restricted. We apologize for any inconvenience.");
    // Alternatively, redirect to a specific "blocked" page:
    // header("Location: /blocked_location.html");
    // exit();
}
// --- END Geolocation Restriction ---


// Existing database connection code follows...
$servername = "localhost"; // Replace with your server name if different
$username = "root";       // Replace with your database username
$password = "";           // Replace with your database password
$dbname = "emigrinfotv_db"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    // Log error to a file or use a more sophisticated error handling mechanism
    // For now, we'll just die and print a generic error to avoid exposing details
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Attempt to select the database
if (!$conn->select_db($dbname)) {
    // Attempt to create the database if it doesn't exist
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS " . $dbname;
    if ($conn->query($sql_create_db) === TRUE) {
        // Successfully created the database, now select it
        if (!$conn->select_db($dbname)) {
            error_log("Database selection failed even after creation: " . $conn->error);
            die("Database selection failed. Please try again later.");
        }
    } else {
        error_log("Database creation failed: " . $conn->error);
        die("Database creation failed. Please try again later.");
    }
}

// Set charset to utf8mb4 for full Unicode support
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // Continue execution if charset setting fails, but log it
}

// Optional: You might want to create the users table here if it doesn't exist,
// or handle that in a separate setup script. For now, this script will just handle connection.
// Example:
/*
$table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($table_sql)) {
    error_log("Error creating users table: " . $conn->error);
    // Don't die here, as other parts of the app might not depend on this specific table immediately
}
*/

// The script will typically be included in other files, so no direct output here.
// Connection object $conn will be available to including scripts.
?>
