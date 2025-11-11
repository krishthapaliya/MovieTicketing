<?php
session_start();
// Include database connection
include('../includes/db_connect.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare statement to check if username exists
    $query = "SELECT * FROM admin_detail WHERE admin_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the username exists
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        // Verify the password
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_name'] = $admin['admin_name'];
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Incorrect password."; 
        }
    } else {
        $errors[] = "User with this username is not registered."; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Netflix Theme</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* Netflix Dark Theme */
body {
    background: linear-gradient(135deg, #141414 0%, #1c1c1c 100%);
    color: #e5e5e5;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

.login-form {
    width: 100%;
    max-width: 400px;
    background-color: #1f1f1f;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.6);
    animation: fadeIn 0.8s ease-in-out;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

.login-form h2 {
    color: #e50914; /* Netflix Red */
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
}

.login-form p {
    text-align: center;
    color: #b3b3b3;
}

.login-form a {
    color: #e50914;
    text-decoration: none;
}

.login-form a:hover {
    text-decoration: underline;
}

.login-form .form-control {
    background-color: #333;
    color: #fff;
    border: 1px solid #555;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 15px;
    transition: all 0.2s ease-in-out;
}

.login-form .form-control:focus {
    border-color: #e50914;
    box-shadow: 0 0 5px #e50914;
    background-color: #222;
    color: #fff;
}

.login-form .btn-primary {
    background-color: #e50914;
    border-color: #e50914;
    width: 100%;
    padding: 10px;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
}

.login-form .btn-primary:hover {
    background-color: #b00710;
    border-color: #b00710;
}

.alert {
    background-color: #2a2a2a;
    color: #e5e5e5;
    border: 1px solid #555;
}

.error {
    color: #ff4c4c;
    font-size: 0.9em;
}
</style>
</head>
<body>

<div class="login-form">
    <h2>Admin Login</h2>
    <p>Not registered yet? <a href="register.php">Register now</a></p>

    <?php if (!empty($errors)): ?>
        <div class="alert">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Login</button>
    </form>
</div>

</body>
</html>
