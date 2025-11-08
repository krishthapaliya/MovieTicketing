<?php
session_start();
// Include database connection
include('includes/db_connect.php');

// Define the insecure hash function IF your existing passwords require it for verification
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

// Initialize error message
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check if the username exists in the user_detail table
    $query = "SELECT id, username, password FROM user_detail WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Always close statements for good practice
    $stmt->close(); 

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // --- SECURITY CHECK (Choose ONE of the two options below) ---
        
        // OPTION 1: Using your custom (insecure) hash function for legacy data
        // IF ($user['password'] == custom_hash($password)) {
        
        // OPTION 2: Using the secure PHP standard (RECOMMENDED for future/new data)
        // IF (password_verify($password, $user['password'])) {
        
        // Using the custom hash for now, assuming your database is set up this way:
        if ($user['password'] == custom_hash($password)) {
            
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: index.php");
            exit();
        } else {
            // Wrong password
            $error_message = "Invalid username or password.";
        }
    } else {
        // Username doesn't exist (Always use a generic error for security)
        $error_message = "Invalid username or password.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
    <style>
        body {
            background-color: #121212; /* Dark background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #e0e0e0;
        }

        .login-card {
            width: 90%;
            max-width: 450px;
            background-color: #1e1e1e; /* Card background */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
        }
        
        .login-card h2 {
            margin-bottom: 30px;
            color: #e50914; /* Netflix Red */
        }
        
        .form-control {
            background-color: #333; /* Dark input fields */
            color: #fff;
            border: 1px solid #555;
        }
        .form-control:focus {
            background-color: #333;
            color: #fff;
            border-color: #e50914;
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }

        .btn-primary {
            background-color: #e50914;
            border-color: #e50914;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #c40811;
            border-color: #c40811;
        }

        a {
            color: #e50914;
            text-decoration: none;
        }
        a:hover {
            color: #ffc107;
        }
    </style>
</head>

<body>
    <div class="login-card container">
        <h2 class="text-center"><i class="fas fa-lock me-2"></i>User Login</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="login-form">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        <div class="mt-4 text-center">
            <p class="text-muted">Don't have an account? <a href="register.php">Register here</a></p>
            <p class="text-muted">Forgot Your Password? <a href="forgot-password.php">Reset here</a></p>
        </div>
    </div>
</body>

</html>