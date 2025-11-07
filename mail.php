<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Include necessary files for PHPMailer
require 'vendor/autoload.php'; // If using Composer
include('includes/db_connect.php'); // Include the database connection
include('includes/header.php'); // Include the header

// Process the booking if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booked_seats'])) {
    $showtime_id = $_POST['showtime_id'];
    $user_id = $_SESSION['user_id']; // Assuming the user is logged in and user_id is stored in session
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $booked_seats = $_POST['booked_seats']; // Seat numbers as comma-separated string
    $date = date('Y-m-d'); // Current date (for the booking record)
    $pricePerSeat = $_POST['pricePerSeat']; // Get price per seat from the form
    $total_price = $_POST['total_price']; // Total price from the form
    $status = 'confirmed'; // Booking status
    $transaction_uuid = generateRandomUuid(); // Use the UUID function

    // Insert the booking into the bookings table
    $booking_query = "INSERT INTO bookings (user_id, date, showtime, price, status, booked_seats) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($booking_query);
    $stmt->bind_param("isisss", $user_id, $date, $showtime_id, $total_price, $status, $booked_seats);

    if ($stmt->execute()) {
        header("Location: epay.php?price=$total_price&transaction_uuid=$transaction_uuid"); 

        // Fetch movie and showtime details
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
            //Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com'; // Set SMTP server to send through
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com'; // SMTP username
            $mail->Password = 'your_password'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port = 587; // TCP port to connect to

            // Recipients
            $mail->setFrom('your_email@example.com', 'Movie Booking');
            $mail->addAddress($email, $name); // Add a recipient

            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = 'Booking Confirmation for ' . $movie['movie_name'];
            $mail->Body = "
                <h3>Thank you for booking with us!</h3>
                <p>You have successfully booked the following movie:</p>
                <p><strong>Movie:</strong> {$movie['movie_name']}</p>
                <p><strong>Date:</strong> {$movie['date']}</p>
                <p><strong>Showtime:</strong> {$movie['start_time']} - {$movie['end_time']}</p>
                <p><strong>Seats:</strong> $booked_seats</p>
                <p><strong>Total Price:</strong> $total_price</p>
                <img src='https://yourwebsite.com/uploaded_img/{$movie['movie_image']}' alt='{$movie['movie_name']}' style='width:200px;' />
                <p>We look forward to seeing you!</p>
            ";

            // Send the email
            $mail->send();

            echo "
            <div class='alert alert-success alert-dismissible fade show' role='alert'>
                Booking successful for seats: $booked_seats. Total Price: $total_price. A confirmation email has been sent to $email.
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
        } catch (Exception $e) {
            echo "
            <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                Booking successful, but we couldn't send the confirmation email. Please contact support.
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
        }
    } else {
        echo "
        <div class='alert alert-danger alert-dismissible fade show' role='alert'>
            Booking failed. Please try again.
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}
?>
