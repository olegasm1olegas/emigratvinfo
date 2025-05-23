<?php
// It's crucial to set cookie params BEFORE session_start() if you want them to apply to the session cookie.
$cookie_lifetime = time() + (30 * 24 * 60 * 60); // 30 days in seconds

// Set session cookie parameters for 1-month expiry, HttpOnly, Secure (if HTTPS), SameSite
// This should ideally be in a central configuration file or called before any session_start().
if (session_status() == PHP_SESSION_NONE) { // Check if session is not already started
    session_set_cookie_params([
        'lifetime' => $cookie_lifetime,
        'path' => '/', // Or your specific application path, '/' is common
        'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '', // Current host, blank if not in HTTP context
        'secure' => isset($_SERVER['HTTPS']), // True if on HTTPS, false otherwise
        'httponly' => true, // Prevent JavaScript access to the session cookie
        'samesite' => 'Lax' // Helps protect against CSRF attacks
    ]);
}

session_start(); // Start session to store messages and user data

require_once 'database.php'; // Includes $conn

$errors = [];

// Clear old errors from session if any, to prevent displaying them again on a fresh visit
if (isset($_SESSION['login_errors']) && $_SERVER["REQUEST_METHOD"] != "POST") {
    unset($_SESSION['login_errors']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // --- Server-Side Validation ---
    if (empty($username_or_email)) {
        $errors[] = "Username or Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            // Log the detailed error, but show a generic one to the user
            error_log("Database error (prepare failed for login select): " . $conn->error);
            $errors[] = "An unexpected error occurred. Please try again later.";
        } else {
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            if (!$stmt->execute()) {
                error_log("Database error (execute failed for login select): " . $stmt->error);
                $errors[] = "An unexpected error occurred during login. Please try again later.";
            } else {
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, authentication successful

                        // Regenerate session ID to prevent session fixation attacks
                        if (!session_regenerate_id(true)) {
                            // Log if regeneration fails, though it's rare
                            error_log("Session regeneration failed for user: " . $user['username']);
                            // Proceed with login, but this is a potential security concern
                        }

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['logged_in_at'] = time(); // Optional: for custom session timeout logic

                        // Clear any login errors from session as login is successful
                        unset($_SESSION['login_errors']);

                        // Redirect to dashboard
                        if (!headers_sent()) {
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            // Fallback if headers already sent (should ideally not happen)
                            echo "Login successful! Redirecting to <a href='dashboard.php'>dashboard</a>...";
                            // You might want to add a meta refresh tag here as a fallback
                            echo '<meta http-equiv="refresh" content="3;url=dashboard.php">';
                            exit();
                        }
                    } else {
                        // Invalid password
                        $errors[] = "Invalid username/email or password.";
                    }
                } else {
                    // User not found
                    $errors[] = "Invalid username/email or password.";
                }
            }
            $stmt->close();
        }
    }

    // --- If there were errors, store them in session and redirect back to login form ---
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        // Store submitted username/email to repopulate the form, enhancing user experience
        $_SESSION['form_data_login'] = ['username_or_email' => $username_or_email];
        if (!headers_sent()) {
            header("Location: login.html");
            exit();
        } else {
            // Fallback if headers already sent
            echo "Login failed. Please go back and try again. Errors: " . implode(", ", $errors);
            exit();
        }
    }
} else {
    // If not a POST request, redirect to login form.
    // Clear any previous error messages if user navigates directly to login.php
    unset($_SESSION['login_errors']);
    unset($_SESSION['form_data_login']);
    if (!headers_sent()) {
        header("Location: login.html");
        exit();
    } else {
        echo "Please login via the <a href='login.html'>login form</a>.";
        exit();
    }
}

// Close the database connection if it's still open and valid
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
?>
