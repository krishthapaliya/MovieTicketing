<?php
// CRITICAL FIX: Conditionally start the session at the very beginning of the included file.
// This prevents the "session already active" notice when header.php is included
// in a main file (like movie_details.php) that also calls session_start().
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// NOTE: I've removed the session_start() line that was previously inside the HTML/PHP block below.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/userstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ticket-alt me-2 text-danger"></i>MovieMania
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_profile.php"><i class="fas fa-user-circle me-1"></i>Profile</a>
                    </li>
                    <?php
                    // Check if the user is logged in
                    if (isset($_SESSION['user_id'])) {
                        echo '
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Log out</a>
                        </li>';
                    } else {
                        // If the user is not logged in, show 'Log in'
                        echo '
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Log in</a>
                        </li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>