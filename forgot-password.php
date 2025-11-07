<?php
include 'includes\db_connect.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';
session_start();

function generate_otp(){
        $otp = '';
        $initial = time() * 989889;
        for ($i = 0; $i < 6; $i++) {
            $otp .= ($initial * ($i + 1)) % 10; 
            $initial += 987; 
        }
        return $otp;
}

function custom_hash($password) {
    $random = 'vfshbhjv@#2343'; 
    $hashed = '';
    for ($i = 0; $i < strlen($password); $i++) {
        $hashed .= dechex(ord($password[$i]) + ord($random[$i % strlen($random)]));
    }
    return $hashed;
}

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    
    $query = "SELECT * FROM user_detail WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $otp = generate_otp();
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $email;

        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();                                      
            $mail->Host = 'smtp.gmail.com';                    
            $mail->SMTPAuth = true;                               
            $mail->Username = 'aryalbikal7@gmail.com';           
            $mail->Password = 'venn xujt zstg dtcb';              
            $mail->SMTPSecure = 'tls';                            
            $mail->Port = 587;                                    

            
            $mail->setFrom('noreply@onlinemovieticketing.com', 'Mango Hall');
            $mail->addAddress($email);                            

            
            $mail->isHTML(true);                                  
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "Your OTP for password reset is: <strong>$otp</strong>";

            
            $mail->send();

            echo "<script>
                alert('A 6-digit OTP has been sent to your email.');
                window.location.href = 'forgot-password.php?step=verify';
            </script>";
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "No account found with that email address.";
    }
}


if (isset($_POST['otp_verify'])) {
    $otp_input = $_POST['otp'];
    
    if ($_SESSION['otp'] == $otp_input) {
        echo "<script>
            alert('OTP verified successfully.');
            window.location.href = 'forgot-password.php?step=reset';
        </script>";
    } else {
        echo "Invalid OTP. Please try again.";
    }
}

if (isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $email = $_SESSION['email'];

    $hashed_password = custom_hash($new_password);
    $updateQuery = "UPDATE user_detail SET password='$hashed_password' WHERE email='$email'";
    mysqli_query($conn, $updateQuery);
    
    unset($_SESSION['otp']);
    unset($_SESSION['email']);
    
    echo "<script>
        alert('Password has been reset successfully.');
        window.location.href = 'login.php';
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        /* Styles for the form */
        .form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
            padding: 20px;
            border-radius: 20px;
            background-color: lightskyblue;
            color: #fff;
            border: 1px solid red;
            margin-top: 30vh;
            margin-left: auto;
            margin-right: auto;
        }
        .title {
            font-size: 32px;
            font-weight: 600;
            text-align: center;
            color: #00bfff;
        }
        .message {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }
        .input {
            background-color: #333;
            color: #fff;
            width: 85%;
            padding: 10px;
            outline: 0;
            border: 1px solid rgba(105, 105, 105, 0.397);
            border-radius: 10px;
        }
        .submit {
            align-self:center;
            width: 50%;
            border: none;
            padding: 10px;
            border-radius: 10px;
            color: #fff;
            background-color: #00bfff;
            cursor: pointer;
        }
        .submit:hover {
            background-color: #00bfff96;
        }
    </style>
</head>
<body>
    <center>
    <form class="form" method="POST" onsubmit="return validateForm()">
    <?php
    // Step 1: Enter Email
    if (!isset($_GET['step'])) {
    ?>
        <p class="title">Forgot Password?</p>
        <p class="message">Enter your registered Email</p>
        <label>
            <input class="input" type="email" name="email" id="email" placeholder="Enter Email" required>
        </label> 
        <button class="submit" type="submit">Submit</button>

    <?php
    // Step 2: Verify OTP
    } elseif ($_GET['step'] == 'verify') {
    ?>
        <p class="title">Verify OTP</p>
        <p class="message">Enter the 4-digit OTP sent to your email</p>
        <label>
            <input class="input" type="text" name="otp" placeholder="Enter OTP" required>
        </label> 
        <button class="submit" name="otp_verify" type="submit">Verify OTP</button>

    <?php
    // Step 3: Reset Password
    } elseif ($_GET['step'] == 'reset') {
    ?>
        <p class="title">Reset Password</p>
        <p class="message">Enter your new password</p>
        <label>
            <input class="input" type="password" name="new_password" id="new_password" placeholder="New Password" required>
        </label> 
        <button class="submit" name="reset_password" type="submit">Reset Password</button>
    <?php
    }
    ?>
</form>

<script>
   function validateForm() {
       <?php if (isset($_GET['step']) && $_GET['step'] == 'reset') { ?>
       // Password validation: must start with a letter, have at least 8 characters, and contain at least one special character
       const password = document.getElementById('new_password').value;
       const passwordRegex = /^[a-zA-Z][\w!@#$%^&*]{7,}$/;  // Starts with letter, 8 characters long, one special char

       if (!passwordRegex.test(password)) {
           alert("Password must start with a letter, be at least 8 characters long, and contain at least one special character.");
           return false;
       }
       <?php } ?>

       <?php if (!isset($_GET['step'])) { ?>
       // Email validation (assuming you'll handle checking registered email server-side)
       const email = document.getElementById('email').value;
       // You could add an AJAX request here to check if the email is registered, 
       // or handle it after the form submission.
       // For now, let's just allow form submission and handle the check on the server side
       <?php } ?>

       return true; // Allow form submission if all validations pass
   }
</script>

    </center>
</body>
</html>
