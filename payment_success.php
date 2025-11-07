
<?php
include('includes/db_connect.php');
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = $_GET['email'];
$amt = $_GET['price'];
$showtime_id = $_GET['showtimeid'];
$booked_seats = $_GET['seats'];

// Fetch movie details based on showtime ID
$movie_query = "SELECT movies.name AS movie_name, movies.image AS movie_image, showtimes.date, showtimes.start_time, showtimes.end_time
FROM movies 
INNER JOIN showtimes ON movies.id = showtimes.movie
WHERE showtimes.id = ?";
$stmt = $conn->prepare($movie_query);
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();

// Send confirmation email
$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'aryalbikal7@gmail.com';
    $mail->Password = 'venn xujt zstg dtcb'; // Use app-specific password for Gmail
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipient
    $mail->setFrom('noreply@onlinemovieticketing.com', 'Mango Hall');
    $mail->addAddress($email);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Booking Confirmation for ' . $movie['movie_name'];
    $mail->Body = "
        <h3>Thank you for booking with us!</h3>
        <p>You have successfully booked the following movie:</p>
        <p><strong>Movie:</strong> {$movie['movie_name']}</p>
        <p><strong>Date:</strong> {$movie['date']}</p>
        <p><strong>Showtime:</strong> {$movie['start_time']} - {$movie['end_time']}</p>
        <p><strong>Seats:</strong> $booked_seats</p>
        <p><strong>Total Price:</strong> $amt</p>
        <img src='https://yourwebsite.com/uploaded_img/{$movie['movie_image']}' alt='{$movie['movie_name']}' style='width:200px;' />
        <p>We look forward to seeing you!</p>
    ";

    // Send the email
    $mail->send();

    echo "
        <div class='alert alert-success alert-dismissible fade show' role='alert'>
        Booking successful for seats: $booked_seats. Total Price: $amt. A confirmation email has been sent to $email.
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
        header("Location: index.php");
} catch (Exception $e) {
    echo "
        <div class='alert alert-danger alert-dismissible fade show' role='alert'>
        Booking successful, but we couldn't send the confirmation email. Please contact support.
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
}
?>
