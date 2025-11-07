<?php
session_start();

function custom_hash($password) {
    $salt = 'vfshbhjv@#2343'; 
    $hashed = '';
    for ($i = 0; $i < strlen($password); $i++) {
        $hashed .= dechex(ord($password[$i]) + ord($salt[$i % strlen($salt)]));
    }
    return $hashed;
}


if (isset($_SESSION['user_id'])) {
    // Redirect to index if logged in
    header("Location: index.php");
    exit();
}
// Include database connection
include('includes/db_connect.php');

// Initialize error message
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the username exists in the user_detail table
    $query = "SELECT * FROM user_detail WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {    
        $user = $result->fetch_assoc();

        // Verify the password
        if (custom_hash($password) == $user['password']) {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to user dashboard or homepage
            header("Location: index.php");
            exit();
        } else {
            // Wrong password
            $error_message = "Invalid password. Please try again.";
        }
    } else {
        // Username doesn't exist
        $error_message = "User of this name is not registered. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-form {
            width: 40%;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .login-form h2 {
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
        }
    </style>
</head>

<body>
    <div class="login-form container">
        <h2>User Login</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="login-form">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="mt-3">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p>Forgot Your Password? <a href="forgot-password.php">reset here</a></p>
        </div>
    </div>
</body>

</html>