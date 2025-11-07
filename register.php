<?php
// Include database connection
include('includes/db_connect.php');
function custom_hash($password) {
    $salt = 'vfshbhjv@#2343'; 
    $hashed = '';
    for ($i = 0; $i < strlen($password); $i++) {
        $hashed .= dechex(ord($password[$i]) + ord($salt[$i % strlen($salt)]));
    }
    return $hashed;
}

// Initialize error messages for PHP-side validation
$errors = [];

// Handle the real-time username check
if (isset($_POST['check_username'])) {
    $username = $_POST['check_username'];
    $query = "SELECT * FROM user_detail WHERE username = ?";
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
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side validation
    if (empty($username) || empty($email) || empty($contact) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (!preg_match("/^[0-9]{10}$/", $contact)) {
        $errors[] = "Please enter a valid 10-digit contact number.";
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
    $query = "SELECT * FROM user_detail WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Username is already taken.";
    }

    // If no errors, insert into the database
    if (empty($errors)) {
        $password_hash = custom_hash($password);

        $query = "INSERT INTO user_detail (username, email, contact, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $username, $email, $contact, $password_hash);

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
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .registration-form {
            width: 60%;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .registration-form h2 {
            margin-bottom: 20px;
        }
        .registration-form .form-control {
            margin-bottom: 10px;
        }
        .error {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="registration-form container">
    <h2>User Registration</h2>
    <div class="mt-3">
    <p>Already registered? <a href="login.php">Go to Login</a></p>
</div>

    <div id="message-area"></div>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
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
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <span id="email-error" class="error"></span>
        </div>
        <div class="mb-3">
            <label for="contact" class="form-label">Contact</label>
            <input type="text" class="form-control" id="contact" name="contact" required>
            <span id="contact-error" class="error"></span>
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
        <button type="submit" id="submit-button" class="btn btn-primary">Register</button>
    </form>
</div>

<script>
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const contactInput = document.getElementById('contact');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitButton = document.getElementById('submit-button');

    const usernameError = document.getElementById('username-error');
    const emailError = document.getElementById('email-error');
    const contactError = document.getElementById('contact-error');
    const passwordError = document.getElementById('password-error');
    const confirmPasswordError = document.getElementById('confirm-password-error');

    const usernamePattern = /^[A-Za-z][A-Za-z0-9]*$/;
    const contactPattern = /^(98|97)\d{8}$/;
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W]).{8,}$/;

    function validateUsername() {
        const username = usernameInput.value;

        if (username.length > 0) {
            if (!usernamePattern.test(username)) {
                usernameError.textContent = "Invalid Username.";
                submitButton.disabled = true;
                return false;
            } else {
                // AJAX call to check if username is already taken
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

    function validateEmail() {
        const email = emailInput.value;

        if (email.length > 0) {
            if (!email.match(/^\S+@\S+\.\S+$/)) {
                emailError.textContent = "Please provide a valid email.";
                submitButton.disabled = true;
                return false;
            } else {
                emailError.textContent =                "";
                validateForm();
            }
        } else {
            emailError.textContent = "";
            submitButton.disabled = true;
        }
    }

    function validateContact() {
        const contact = contactInput.value;

        if (contact.length > 0) {
            if (!contactPattern.test(contact)) {
                contactError.textContent = "Please enter a valid 10-digit contact number.";
                submitButton.disabled = true;
                return false;
            } else {
                contactError.textContent = "";
                validateForm();
            }
        } else {
            contactError.textContent = "";
            submitButton.disabled = true;
        }
    }

    function validatePassword() {
        const password = passwordInput.value;

        if (password.length > 0) {
            if (!passwordPattern.test(password)) {
                passwordError.textContent = "Password must be at least 8 characters long with a mix of uppercase, lowercase, numbers, and symbols.";
                submitButton.disabled = true;
                return false;
            } else {
                passwordError.textContent = "";
                validateForm();
            }
        } else {
            passwordError.textContent = "";
            submitButton.disabled = true;
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
                validateForm();
            }
        } else {
            confirmPasswordError.textContent = "";
            submitButton.disabled = true;
        }
    }

    function validateForm() {
        if (
            usernameError.textContent === "" &&
            emailError.textContent === "" &&
            contactError.textContent === "" &&
            passwordError.textContent === "" &&
            confirmPasswordError.textContent === "" &&
            usernameInput.value.length > 0 &&
            emailInput.value.length > 0 &&
            contactInput.value.length > 0 &&
            passwordInput.value.length > 0 &&
            confirmPasswordInput.value.length > 0
        ) {
            submitButton.disabled = false;
        } else {
            submitButton.disabled = true;
        }
    }

    // Event listeners for real-time validation
    usernameInput.addEventListener("input", validateUsername);
    emailInput.addEventListener("input", validateEmail);
    contactInput.addEventListener("input", validateContact);
    passwordInput.addEventListener("input", validatePassword);
    confirmPasswordInput.addEventListener("input", validateConfirmPassword);

    // Initial validation to disable submit button if form is incomplete
    document.addEventListener("DOMContentLoaded", validateForm);
</script>

</body>
</html>

