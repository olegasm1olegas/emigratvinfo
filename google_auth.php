<?php
session_start();
require_once 'database.php'; // Provides $conn

// The user needs to install this library via composer:
// composer require google/apiclient:^2.0
// And then include the autoload.php:
// require_once 'vendor/autoload.php'; 
// For this subtask, if composer/vendor setup is complex,
// the worker can simulate the Google_Client verification part and focus on DB logic.

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_POST['idtoken'])) {
    $response['message'] = 'ID token not provided.';
    echo json_encode($response);
    exit;
}

$id_token = $_POST['idtoken'];

// ** SIMULATED Google Token Verification - WORKER: REPLACE WITH ACTUAL LIBRARY IF POSSIBLE **
// If Google API Client Library is available and configured:
/*
try {
    // Ensure vendor/autoload.php is included if you use the Google API Client Library
    // require_once 'vendor/autoload.php'; 
    $client = new Google_Client(['client_id' => 'YOUR_GOOGLE_CLIENT_ID_PLACEHOLDER']); // Replace with actual Client ID
    $payload = $client->verifyIdToken($id_token);

    if ($payload) {
        $google_user_id = $payload['sub'];
        $email = isset($payload['email']) ? $payload['email'] : null;
        $email_verified = isset($payload['email_verified']) ? $payload['email_verified'] : false;
        $name = isset($payload['name']) ? $payload['name'] : null; 

        if (!$email || !$email_verified) {
            $response['message'] = 'Google email not available or not verified.';
            echo json_encode($response);
            exit;
        }
        // Actual logic continues here...
    } else {
        $response['message'] = 'Invalid Google ID token.';
        echo json_encode($response);
        exit;
    }
} catch (Exception $e) {
    error_log("Google Sign-In Error: " . $e->getMessage());
    $response['message'] = 'Google Sign-In verification failed: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}
*/

// ** Using SIMULATED PAYLOAD for now to focus on database logic if library is an issue **
$is_simulation = true; // Set to false if Google_Client is used
$payload = null; // Initialize payload

if ($is_simulation) {
    if ($id_token === "test_token_for_new_user@example.com") { // Simulate a new user
        $payload = [
            'sub' => 'SIMULATED_GOOGLE_ID_' . uniqid(),
            'email' => 'new_google_user_' . rand(1000,9999) . '@example.com',
            'email_verified' => true,
            'name' => 'Simulated New User'
        ];
    } elseif ($id_token === "test_token_for_existing_user_no_google_id@example.com") { // Simulate existing email, no google ID
         $payload = [
            'sub' => 'SIMULATED_GOOGLE_ID_' . uniqid(),
            'email' => 'user_exists_no_google@example.com', // Create this user manually in DB for test
            'email_verified' => true,
            'name' => 'Simulated Existing User'
        ];
    } elseif ($id_token === "test_token_for_existing_google_user@example.com") { // Simulate existing Google ID
         $payload = [
            'sub' => 'EXISTING_GOOGLE_ID_123', // Create this user manually in DB for test
            'email' => 'user_exists_with_google@example.com', // Create this user manually in DB for test
            'email_verified' => true,
            'name' => 'Simulated Existing Google User'
        ];
    } else { // Default case for simulation if token doesn't match specific test cases
        $response['message'] = 'Invalid simulated ID token for testing purposes.';
        echo json_encode($response);
        exit;
    }
}
// ** End of SIMULATION Block **

// Ensure payload is set (either by real verification or simulation)
if (!$payload) {
    // This case would be hit if not in simulation mode and Google verification failed silently or was bypassed
    $response['message'] = 'User data payload not available.';
    echo json_encode($response);
    exit;
}


// Extract info from $payload (either real or simulated)
$google_user_id = $payload['sub'];
$email = $payload['email'];
$name = isset($payload['name']) ? $payload['name'] : null; 

// Check if user exists with this google_id
$stmt = $conn->prepare("SELECT id, username, email FROM users WHERE google_id = ?");
if (!$stmt) { 
    error_log("DB error (google_id check prepare): " . $conn->error);
    $response['message'] = 'Database error. Please try again later.'; 
    echo json_encode($response); exit; 
}
$stmt->bind_param("s", $google_user_id);
if (!$stmt->execute()) {
    error_log("DB error (google_id check execute): " . $stmt->error);
    $response['message'] = 'Database error. Please try again later.'; 
    echo json_encode($response); exit; 
}
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // User found with google_id - Log them in
    if (!session_regenerate_id(true)) { error_log("Session regeneration failed for user ID: " . $user['id']); }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['logged_in_at'] = time();
    $response = ['success' => true, 'redirect_url' => 'dashboard.php'];
} else {
    // No user with this google_id. Check by email.
    $stmt_email = $conn->prepare("SELECT id, username, email, google_id FROM users WHERE email = ?");
    if (!$stmt_email) { 
        error_log("DB error (email check prepare): " . $conn->error);
        $response['message'] = 'Database error. Please try again later.'; 
        echo json_encode($response); exit; 
    }
    $stmt_email->bind_param("s", $email);
    if (!$stmt_email->execute()) {
        error_log("DB error (email check execute): " . $stmt_email->error);
        $response['message'] = 'Database error. Please try again later.'; 
        echo json_encode($response); exit; 
    }
    $result_email = $stmt_email->get_result();

    if ($user_by_email = $result_email->fetch_assoc()) {
        // User found with this email. Link Google ID if null.
        if ($user_by_email['google_id'] === NULL) {
            $stmt_update_google_id = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            if (!$stmt_update_google_id) { 
                error_log("DB error (update google_id prepare): " . $conn->error);
                $response['message'] = 'Database error. Please try again later.'; 
                echo json_encode($response); exit; 
            }
            $stmt_update_google_id->bind_param("si", $google_user_id, $user_by_email['id']);
            if ($stmt_update_google_id->execute()) {
                // Successfully linked. Log them in.
                if (!session_regenerate_id(true)) { error_log("Session regeneration failed for user ID: " . $user_by_email['id']); }
                $_SESSION['user_id'] = $user_by_email['id'];
                $_SESSION['username'] = $user_by_email['username']; // Use existing username
                $_SESSION['logged_in_at'] = time();
                $response = ['success' => true, 'redirect_url' => 'dashboard.php'];
            } else {
                error_log("DB error (update google_id execute): " . $stmt_update_google_id->error);
                $response['message'] = 'Failed to link Google account to existing email.';
            }
            $stmt_update_google_id->close();
        } else {
            // Email exists but is linked to a DIFFERENT google_id. This is an edge case.
            $response['message'] = 'This email is already associated with a different Google account. Please log in with the original method or the other Google account.';
        }
    } else {
        // No user by google_id or email - Create new user
        $username_base = '';
        if ($name) { // Try to use the name from Google profile if available
            $username_base = strtolower(preg_replace("/\s+/", "", $name)); // Remove spaces, lowercase
        }
        if (empty($username_base)) { // Fallback to email prefix if name is not useful
            $username_base = strstr($email, '@', true);
        }
        $username_base = preg_replace("/[^a-zA-Z0-9_]/", "", $username_base); // Sanitize
        if (empty($username_base) || strlen($username_base) < 3) $username_base = 'user' . rand(100,999); // Ensure base is not too short
        
        $username = $username_base;
        $counter = 1;
        while (true) {
            $stmt_check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
            if (!$stmt_check_username) { 
                error_log("DB error (username check prepare): " . $conn->error);
                $response['message'] = 'Database error during username check.'; echo json_encode($response); exit; 
            }
            $stmt_check_username->bind_param("s", $username);
            if (!$stmt_check_username->execute()) {
                 error_log("DB error (username check execute): " . $stmt_check_username->error);
                 $response['message'] = 'Database error during username check.'; echo json_encode($response); exit; 
            }
            $stmt_check_username->store_result(); // Important for num_rows
            if ($stmt_check_username->num_rows == 0) {
                $stmt_check_username->close();
                break; // Unique username found
            }
            $stmt_check_username->close();
            $username = $username_base . $counter;
            $counter++;
            if ($counter > 100) { // Safety break
                error_log("Could not generate unique username for base: " . $username_base);
                $response['message'] = 'Could not generate a unique username. Please contact support.'; echo json_encode($response); exit;
            }
        }

        $stmt_insert = $conn->prepare("INSERT INTO users (username, email, google_id, password) VALUES (?, ?, ?, NULL)");
        if (!$stmt_insert) { 
            error_log("DB error (insert user prepare): " . $conn->error);
            $response['message'] = 'Database error. Could not create account.'; echo json_encode($response); exit; 
        }
        $stmt_insert->bind_param("sss", $username, $email, $google_user_id);
        if ($stmt_insert->execute()) {
            $new_user_id = $conn->insert_id;
            if (!session_regenerate_id(true)) { error_log("Session regeneration failed for new user ID: " . $new_user_id); }
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;
            $_SESSION['logged_in_at'] = time();
            $response = ['success' => true, 'redirect_url' => 'dashboard.php'];
        } else {
            error_log("DB error (insert user execute): " . $stmt_insert->error . " (errno: " . $conn->errno . ")");
            if ($conn->errno == 1062) { 
                 $response['message'] = 'This email or a derived username is already registered. Try logging in or using a different Google account.';
            } else {
                 $response['message'] = 'Failed to create new user account due to a database error.';
            }
        }
        $stmt_insert->close();
    }
    if (isset($stmt_email) && $stmt_email instanceof mysqli_stmt) $stmt_email->close();
}
if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

echo json_encode($response);
?>
