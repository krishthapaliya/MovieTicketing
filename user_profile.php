<?php
// Include database connection
include('includes/db_connect.php');

// FIX: Ensure session_start() is not called twice. 
// If it's already in a required file (like 'includes/header.php'), remove it here.
// For this standalone file, we'll keep it, but ensure it's at the top.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$stmt->close(); // Close statement after fetching data

// Initialize error messages for PHP-side validation
$errors = [];
$success_message = "";

// --- Handle AJAX Username Check (Must run before the main POST handler) ---
// Note: This logic must be moved to the beginning to allow AJAX to execute and exit.
if (isset($_POST['username']) && isset($_POST['user_id']) && !isset($_POST['contact'])) {
    $username = $_POST['username'];
    $check_user_id = $_POST['user_id'];

    // Check if the username exists in the database, excluding the current user
    $query = "SELECT id FROM user_detail WHERE username = ? AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $username, $check_user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "exists";
    } else {
        echo "available";
    }
    $stmt->close();
    exit; // IMPORTANT: Exit here to prevent full page rendering for AJAX
}


// --- Handle Form Submission for updating user details ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side validation
    if (empty($username) || empty($email) || empty($contact)) {
        $errors[] = "All fields are required.";
    }

    // Check if username is valid
    if (!preg_match("/^[A-Za-z][A-Za-z0-9]*$/", $username)) {
        $errors[] = "Username must start with a letter and contain only letters and numbers.";
    }
    
    // Re-check username availability (in case of failed client-side check)
    if (empty($errors)) {
        $query = "SELECT id FROM user_detail WHERE username = ? AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username is already taken by another user.";
        }
        $stmt->close();
    }


    // Validate email format and prevent invalid ones like 100@200.com
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match("/^\d+@\d+\.\w+$/", $email)) {
        $errors[] = "Invalid email address format.";
    }

    // Password validation only if provided
    if (!empty($password) || !empty($confirm_password)) {
        if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) ||
            !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) ||
            !preg_match("/[\W]/", $password)) {
            $errors[] = "Password must be at least 8 characters long and include an uppercase, lowercase, number, and symbol.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    // Update user details if no errors
    if (empty($errors)) {
        $update_query = "UPDATE user_detail SET username = ?, email = ?, contact = ?";
        $params = [$username, $email, $contact];
        $types = "sss";

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_query .= ", password = ?";
            $params[] = $password_hash;
            $types .= "s";
        }
        
        $update_query .= " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";
        
        // Prepare and execute the final update statement
        $stmt = $conn->prepare($update_query);
        
        // Dynamic binding
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            
            // Re-fetch the current user details to update the form fields instantly
            $query = "SELECT username, email, contact FROM user_detail WHERE id = ?";
            $stmt_fetch = $conn->prepare($query);
            $stmt_fetch->bind_param("i", $user_id);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $user = $result_fetch->fetch_assoc();
            $stmt_fetch->close();
            
        } else {
            $errors[] = "Database Error: " . $conn->error;
        }
        $stmt->close();
    }
}
// ------------------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/userstyle.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom styles for dark theme look */
        body {
            background-color: #121212; /* Very dark background */
            color: #e0e0e0; /* Light text */
        }
        .container {
            padding-top: 50px;
        }
        .profile-card {
            background-color: #1e1e1e; /* Slightly lighter card background */
            border: 1px solid #333;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        .form-control {
            background-color: #333 !important; /* Dark input fields */
            color: #fff !important;
            border: 1px solid #555;
        }
        .form-control:focus {
            background-color: #333;
            color: #fff;
            border-color: #e50914; /* Highlight focus with a primary color */
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }
        .btn-primary {
            background-color: #e50914; /* Netflix Red */
            border-color: #e50914;
        }
        .error {
            color: #ffc107; /* Warning yellow for errors */
            font-size: 0.9em;
            display: block; /* Ensure it takes full width */
            margin-top: 5px;
        }
        .available {
            color: #28a745; /* Green for success */
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card profile-card">
                    <div class="card-header bg-dark text-white border-bottom border-danger">
                        <h2 class="mb-0"><i class="fas fa-user-circle me-2 text-danger"></i>Update Your Profile</h2>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Please correct the following errors:</strong>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="profile-form">
                            <input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label"><i class="fas fa-user me-2"></i>Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <span id="username-error" class="error"></span>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <span id="email-error" class="error"></span>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact" class="form-label"><i class="fas fa-phone me-2"></i>Contact Number</label>
                                <input type="text" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" required>
                                <span id="contact-error" class="error"></span>
                            </div>
                            
                            <hr class="text-secondary mt-4 mb-4">
                            
                            <p class="text-muted"><i class="fas fa-lock me-1"></i> **Change Password** (Leave both fields blank to keep current password)</p>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <span id="password-error" class="error"></span>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <span id="confirm-password-error" class="error"></span>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const contactInput = document.getElementById('contact');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const userIdInput = document.getElementById('user_id'); // Get the hidden user_id input

        const usernameError = document.getElementById('username-error');
        const emailError = document.getElementById('email-error');
        const contactError = document.getElementById('contact-error');
        const passwordError = document.getElementById('password-error');
        const confirmPasswordError = document.getElementById('confirm-password-error');

        const usernamePattern = /^[A-Za-z][A-Za-z0-9]*$/;
        
        let usernameValid = true;
        let emailValid = true;

        // --- AJAX Username Availability Check ---
        function checkUsernameAvailability() {
            const username = usernameInput.value;
            const currentUserId = userIdInput.value;

            // Client-side validation for pattern first
            if (username.length < 3) {
                usernameError.textContent = "Username must be at least 3 characters.";
                usernameValid = false;
                return;
            }
            if (!usernamePattern.test(username)) {
                usernameError.textContent = "Username must start with a letter (A-Z, a-z).";
                usernameValid = false;
                return;
            }

            // AJAX call to check database
            const xhr = new XMLHttpRequest();
            // IMPORTANT: The request is sent to the same file (user_profile.php)
            xhr.open("POST", "user_profile.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = xhr.responseText.trim();
                    if (response === "exists") {
                        usernameError.textContent = "Username is already taken.";
                        usernameInput.classList.add('is-invalid');
                        usernameInput.classList.remove('is-valid');
                        usernameValid = false;
                    } else if (response === "available") {
                        usernameError.textContent = "Username is available!";
                        usernameError.classList.add('available');
                        usernameInput.classList.remove('is-invalid');
                        usernameInput.classList.add('is-valid');
                        usernameValid = true;
                    } else {
                         // Clear error if it's the current user's name
                         usernameError.textContent = "";
                         usernameError.classList.remove('available');
                         usernameInput.classList.remove('is-invalid');
                         usernameInput.classList.add('is-valid');
                         usernameValid = true;
                    }
                }
            };
            xhr.send(`username=${encodeURIComponent(username)}&user_id=${currentUserId}`);
        }

        // --- Client-side Email Validation ---
        function validateEmail() {
            const email = emailInput.value;
            emailError.classList.remove('available');

            if (!/\S+@\S+\.\S+/.test(email) || /^\d+@\d+\.\w+$/.test(email)) {
                emailError.textContent = "Invalid email address format.";
                emailInput.classList.add('is-invalid');
                emailInput.classList.remove('is-valid');
                emailValid = false;
            } else {
                emailError.textContent = "";
                emailInput.classList.remove('is-invalid');
                emailInput.classList.add('is-valid');
                emailValid = true;
            }
        }
        
        // --- Client-side Password Validation ---
        function validatePassword() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            let valid = true;
            passwordError.textContent = "";

            if (password.length > 0) {
                if (password.length < 8) {
                    passwordError.textContent += "Password must be at least 8 characters long. ";
                    valid = false;
                }
                if (!/[A-Z]/.test(password)) {
                    passwordError.textContent += "Needs an uppercase letter. ";
                    valid = false;
                }
                if (!/[a-z]/.test(password)) {
                    passwordError.textContent += "Needs a lowercase letter. ";
                    valid = false;
                }
                if (!/[0-9]/.test(password)) {
                    passwordError.textContent += "Needs a number. ";
                    valid = false;
                }
                if (!/[\W]/.test(password)) {
                    passwordError.textContent += "Needs a symbol (non-alphanumeric).";
                    valid = false;
                }
            }
            return valid;
        }

        function validateConfirmPassword() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            confirmPasswordError.textContent = "";

            // Only check if password field is not empty
            if (password.length > 0 && password !== confirmPassword) {
                confirmPasswordError.textContent = "Passwords do not match.";
                return false;
            }
            return true;
        }

        // --- Event Listeners ---
        usernameInput.addEventListener('blur', checkUsernameAvailability);
        usernameInput.addEventListener('input', () => {
            // Clear availability message on input
            usernameError.textContent = ""; 
            usernameError.classList.remove('available');
            usernameInput.classList.remove('is-invalid', 'is-valid');
        });
        emailInput.addEventListener('blur', validateEmail);
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validateConfirmPassword);


        // --- Final Form Submission Validation ---
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            // Re-run all essential validations on submit
            const isPasswordValid = validatePassword();
            const isConfirmPasswordValid = validateConfirmPassword();
            validateEmail(); // Update emailValid variable
            
            // Check if client-side username check passed
            if (!usernameValid || !emailValid || !isPasswordValid || !isConfirmPasswordValid) {
                e.preventDefault();
                alert('Please correct the highlighted errors before submitting.');
            }
        });
    </script>
</body>
</html>