<?php
session_start(); // Start session to store messages if needed

require_once 'database.php'; // Includes $conn

// Attempt to create the users table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE, -- We might need to auto-generate or ask if Google user is new
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NULL, -- Allow NULL for Google-only sign-ins
    google_id VARCHAR(255) NULL UNIQUE,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($table_sql)) {
    error_log("Error creating users table: " . $conn->error);
    // Handle error appropriately - maybe redirect to an error page or show a message
    // For this script, we'll set an error and attempt to redirect, or die if headers sent.
    $_SESSION['register_errors'] = ["Error setting up registration. Please try again later."];
    if (!headers_sent()) {
        header("Location: register.html");
        exit();
    } else {
        die("Error setting up registration. Please try again later. Cannot redirect.");
    }
}

$errors = [];
// $success_message = ''; // Not used directly, using session for success message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Keep original for length check before hashing

    // --- Server-Side Validation ---
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    // --- Check for Existing User/Email (if no validation errors so far) ---
    if (empty($errors)) {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt_check) {
            // Log the detailed error, but show a generic one to the user
            error_log("Database error (prepare failed for check): " . $conn->error);
            $errors[] = "An unexpected error occurred. Please try again later.";
        } else {
            $stmt_check->bind_param("ss", $username, $email);
            if ($stmt_check->execute()) {
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $errors[] = "Username or email already taken.";
                }
            } else {
                error_log("Database error (execute failed for check): " . $stmt_check->error);
                $errors[] = "An unexpected error occurred during user check. Please try again later.";
            }
            $stmt_check->close();
        }
    }

    // --- If no errors, proceed to hash password and insert ---
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
            error_log("Password hashing failed for user: " . $username);
            $errors[] = "An critical error occurred during registration. Please try again.";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if (!$stmt_insert) {
                 error_log("Database error (prepare failed for insert): " . $conn->error);
                 $errors[] = "An unexpected error occurred. Please try again later.";
            } else {
                $stmt_insert->bind_param("sss", $username, $email, $hashed_password);
                if ($stmt_insert->execute()) {
                    // Success - redirect to login page with a success message
                    $_SESSION['success_message'] = "Registration successful! Please login.";
                    if (!headers_sent()) {
                        header("Location: login.html");
                        exit();
                    } else {
                        // Fallback if headers already sent (should ideally not happen with proper structure)
                        echo "Registration successful! Please <a href='login.html'>login</a>.";
                        exit();
                    }
                } else {
                    error_log("Registration failed (execute failed for insert): " . $stmt_insert->error . " for user " . $username);
                    // Check for duplicate entry error specifically, though the previous check should catch it
                    if ($conn->errno == 1062) { // MySQL error code for duplicate entry
                        $errors[] = "Username or email already taken.";
                    } else {
                        $errors[] = "Registration failed. Please try again.";
                    }
                }
                $stmt_insert->close();
            }
        }
    }

    // --- If there were errors, store them in session and redirect back to register form ---
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        // Store form values to repopulate the form, enhancing user experience
        $_SESSION['form_data'] = ['username' => $username, 'email' => $email];
        if (!headers_sent()) {
            header("Location: register.html");
            exit();
        } else {
            // Fallback if headers already sent
            echo "Registration failed. Please go back and try again. Errors: " . implode(", ", $errors);
            exit();
        }
    }
} else {
    // If not a POST request, redirect to registration form.
    // Clear any previous error/success messages if user navigates directly to register.php
    unset($_SESSION['register_errors']);
    unset($_SESSION['form_data']);
    unset($_SESSION['success_message']);
    if (!headers_sent()) {
        header("Location: register.html");
        exit();
    } else {
        echo "Please register via the <a href='register.html'>registration form</a>.";
        exit();
    }
}

// It's good practice to close the connection if it's no longer needed,
// though PHP usually handles this at the end of script execution.
// However, since database.php might be included in other long-running scripts (admin panel etc.),
// explicitly closing it here is fine.
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
