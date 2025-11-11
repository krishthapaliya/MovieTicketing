<?php
// Include database connection
include('../includes/db_connect.php');

// Initialize error messages for PHP-side validation
$errors = [];

// Handle the real-time username check
if (isset($_POST['check_username'])) {
    $username = $_POST['check_username'];
    $query = "SELECT * FROM admin_detail WHERE admin_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
    exit();
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['check_username'])) {
    // Get form inputs
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }

    if (!preg_match("/^[A-Za-z][A-Za-z0-9]*$/", $username)) {
        $errors[] = "Username cannot start with a number or be numeric only.";
    }

    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) ||
        !preg_match("/[\W]/", $password)) {
        $errors[] = "Choose a strong password.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if the username already exists
    $query = "SELECT * FROM admin_detail WHERE admin_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Username is already taken.";
    }

    // If no errors, insert into the database
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO admin_detail (admin_name, password) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $password_hash);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Registration successful! Redirecting to login...</div>";
            header("refresh:2;url=login.php");
            exit();
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
<title>Admin Registration - Netflix Theme</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* 🌑 Netflix Dark Theme */
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

.registration-form {
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

.registration-form h2 {
    color: #e50914; /* Netflix Red */
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
}

.registration-form p {
    text-align: center;
    color: #b3b3b3;
}

.registration-form a {
    color: #e50914;
    text-decoration: none;
}

.registration-form a:hover {
    text-decoration: underline;
}

.registration-form .form-control {
    background-color: #333;
    color: #fff;
    border: 1px solid #555;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 15px;
    transition: all 0.2s ease-in-out;
}

.registration-form .form-control:focus {
    border-color: #e50914;
    box-shadow: 0 0 5px #e50914;
    background-color: #222;
    color: #fff;
}

.registration-form .btn-primary {
    background-color: #e50914;
    border-color: #e50914;
    width: 100%;
    padding: 10px;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
}

.registration-form .btn-primary:hover {
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

#message-area {
    margin-bottom: 10px;
}
</style>
</head>
<body>

<div class="registration-form">
    <h2>Admin Registration</h2>
    <p>Already registered? <a href="login.php">Login here</a></p>

    <div id="message-area"></div>
    <?php if (!empty($errors)): ?>
        <div class="alert">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="registration-form">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <span id="username-error" class="error"></span>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <span id="password-error" class="error"></span>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            <span id="confirm-password-error" class="error"></span>
        </div>
        <button type="submit" id="submit-button" class="btn btn-primary mt-2">Register</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const usernameInput = document.getElementById('username');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');
const submitButton = document.getElementById('submit-button');

const usernameError = document.getElementById('username-error');
const passwordError = document.getElementById('password-error');
const confirmPasswordError = document.getElementById('confirm-password-error');

const usernamePattern = /^[A-Za-z][A-Za-z0-9]*$/;
const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W]).{8,}$/;

function validateUsername() {
    const username = usernameInput.value;
    if (username.length > 0) {
        if (!usernamePattern.test(username)) {
            usernameError.textContent = "Invalid Admin Name.";
            submitButton.disabled = true;
            return false;
        } else {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.exists) {
                        usernameError.textContent = "Username is already taken.";
                        submitButton.disabled = true;
                    } else {
                        usernameError.textContent = "";
                        validateForm();
                    }
                }
            };
            xhr.send('check_username=' + encodeURIComponent(username));
        }
    } else {
        usernameError.textContent = "";
        submitButton.disabled = true;
    }
}

function validatePassword() {
    const password = passwordInput.value;
    if (password.length > 0) {
        if (!passwordPattern.test(password)) {
            passwordError.textContent = "Choose a strong password.";
            submitButton.disabled = true;
            return false;
        } else {
            passwordError.textContent = "";
            return true;
        }
    } else {
        passwordError.textContent = "";
    }
}

function validateConfirmPassword() {
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    if (confirmPassword.length > 0) {
        if (password !== confirmPassword) {
            confirmPasswordError.textContent = "Passwords do not match.";
            submitButton.disabled = true;
            return false;
        } else {
            confirmPasswordError.textContent = "";
            return true;
        }
    } else {
        confirmPasswordError.textContent = "";
    }
}

function validateForm() {
    const isUsernameValid = usernameError.textContent === "";
    const isPasswordValid = validatePassword();
    const isConfirmPasswordValid = validateConfirmPassword();
    submitButton.disabled = !(isUsernameValid && isPasswordValid && isConfirmPasswordValid);
}

usernameInput.addEventListener('keyup', validateUsername);
passwordInput.addEventListener('keyup', validateForm);
confirmPasswordInput.addEventListener('keyup', validateForm);
</script>

</body>
</html>
