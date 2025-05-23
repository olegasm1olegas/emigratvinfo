<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // If not logged in, store an error message in session and redirect to login page
    $_SESSION['login_errors'] = ["Please login to access the dashboard."];
    if (!headers_sent()) {
        header("Location: login.html");
        exit();
    } else {
        // Fallback if headers already sent (should ideally not happen)
        echo "Please login to access the dashboard. <a href='login.html'>Login here</a>.";
        exit();
    }
}

// Sanitize user data from session for display
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$user_id = isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id']) : 'N/A';
$logged_in_at_timestamp = isset($_SESSION['logged_in_at']) ? $_SESSION['logged_in_at'] : null;
$logged_in_at_display = $logged_in_at_timestamp ? date('Y-m-d H:i:s', $logged_in_at_timestamp) : 'N/A';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EmigrInfoTV</title>
    <link rel="stylesheet" href="style.css">
    <meta name="keywords" content="Румынское телевидение, Румынское телевидение в Европе, dashboard, user account">
    <meta name="description" content="EmigrInfoTV - User Dashboard. Access your account details and settings.">
    <meta name="robots" content="noindex, nofollow"> <!-- Typically, dashboard pages are not indexed -->
</head>
<body>
    <header>
        <div class="logo">Emigr TV</div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Welcome to your Dashboard, <?php echo $username; ?>!</h1>
        <p>This is your personal dashboard area. Here's some information we have for you:</p>
        <ul>
            <li>Your User ID is: <strong><?php echo $user_id; ?></strong></li>
            <li>Your registered username is: <strong><?php echo $username; ?></strong></li>
            <li>You logged in at: <strong><?php echo $logged_in_at_display; ?></strong> (Server time)</li>
        </ul>
        
        <section class="dashboard-actions">
            <h2>Quick Actions</h2>
            <p><a href="logout.php" class="button-like">Logout</a></p>
            <!-- More actions can be added here, e.g., link to profile settings page -->
            <!-- <p><a href="profile.php" class="button-like">Edit Profile</a></p> -->
        </section>

        <p>More features will be added here soon!</p>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> EmigrInfoTV. All rights reserved.</p>
    </footer>
</body>
</html>
