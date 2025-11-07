<?php
// Include database connection
include('includes/db_connect.php');
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT username, email, contact FROM user_detail WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize error messages for PHP-side validation
$errors = [];

// Handle form submission for updating user details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $username = $_POST['username'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side validation
    if (empty($username) || empty($email) || empty($contact)) {
        $errors[] = "All fields are required.";
    }

    // Check if username is valid
    if (!preg_match("/^[A-Za-z][A-Za-z0-9]*$/", $username)) {
        $errors[] = "Username cannot start with a number or be numeric only.";
    }


if (isset($_POST['username']) && isset($_POST['user_id'])) {
    $username = $_POST['username'];
    $user_id = $_POST['user_id'];

    // Check if the username exists in the database, excluding the current user
    $query = "SELECT id FROM user_detail WHERE username = ? AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "exists";
    } else {
        echo "available";
    }
}

    // Validate email format and prevent invalid ones like 100@200.com
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match("/^\d+@\d+\.\w+$/", $email)) {
        $errors[] = "Invalid email address.";
    }

    // Password validation only if provided
    if (!empty($password) || !empty($confirm_password)) {
        if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) ||
            !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) ||
            !preg_match("/[\W]/", $password)) {
            $errors[] = "Choose a strong password.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    // Update user details if no errors
    if (empty($errors)) {
        $update_query = "UPDATE user_detail SET username = ?, email = ?, contact = ?";

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_query .= ", password = ?";
            $stmt = $conn->prepare($update_query . " WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $email, $contact, $password_hash, $user_id);
        } else {
            $stmt = $conn->prepare($update_query . " WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $contact, $user_id);
        }

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Profile updated successfully!</div>";
            // Fetch updated user details
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/userstyle.css">
    <style>
        .error {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>User Profile</h2>
        <div id="message-area"></div>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="profile-form">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                <span id="username-error" class="error"></span>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                <span id="email-error" class="error"></span>
            </div>
            <div class="mb-3">
                <label for="contact" class="form-label">Contact</label>
                <input type="text" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" required>
                <span id="contact-error" class="error"></span>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" class="form-control" id="password" name="password">
                <span id="password-error" class="error"></span>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                <span id="confirm-password-error" class="error"></span>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <script>
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const contactInput = document.getElementById('contact');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        const usernameError = document.getElementById('username-error');
        const emailError = document.getElementById('email-error');
        const contactError = document.getElementById('contact-error');
        const passwordError = document.getElementById('password-error');
        const confirmPasswordError = document.getElementById('confirm-password-error');

        const usernamePattern = /^[A-Za-z][A-Za-z0-9]*$/;

        function checkUsernameAvailability() {
            const username = usernameInput.value;
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "user_profile.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = xhr.responseText;
                    if (response === "exists") {
                        usernameError.textContent = "Username already exists.";
                    } else {
                        usernameError.textContent = "";
                    }
                }
            };
            xhr.send(`username=${username}&user_id=<?php echo $user_id; ?>`);
        }

        function validateEmail() {
            const email = emailInput.value;
            if (!/\S+@\S+\.\S+/.test(email) || /^\d+@\d+\.\w+$/.test(email)) {
                emailError.textContent = "Invalid email address.";
                return false;
            } else {
                emailError.textContent = "";
                return true;
            }
        }

        usernameInput.addEventListener('input', checkUsernameAvailability);
        emailInput.addEventListener('input', validateEmail);
    </script>
</body>
</html>
