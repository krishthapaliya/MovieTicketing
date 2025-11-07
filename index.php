<?php
include('includes/db_connect.php'); // Include the database connection
include('includes/header.php'); // Include the header

if (isset($_GET['data'])) {
    // Decode the base64 encoded data
    $data = base64_decode($_GET['data']);
    $transaction_data = json_decode($data, true);

    // Validate the transaction data
    if ($transaction_data) {
        $transaction_uuid = $transaction_data['transaction_uuid']; // Adjust according to your actual data structure
        $status = $transaction_data['status']; // Payment status
        $total_amount = $transaction_data['total_amount']; // Total amount paid
        // Prepare response message
        $message = '';

        // Here, you might want to check the status and update your database accordingly
        if ($status == 'COMPLETE') {
            // Update booking status to completed in the database
            $stmt = $conn->prepare("UPDATE bookings SET status='Completed' WHERE transaction_uuid=?");
            $stmt->bind_param('s', $transaction_uuid);
            if ($stmt->execute()) {
                $message = "Payment successful! Your booking has been confirmed.";

            } else {
                $message = "Payment was successful, but we couldn't update your booking status.";
            }
            $stmt->close();
        } else {
            $message = "Payment failed or is pending! Please contact support.";
        }
    } else {
        $message = "Invalid payment data received.";
    }
} else {
    $message = "No payment data received.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieMania</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/userstyle.css">
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Welcome to MovieMania</h2>
`
        <!-- Fetch and display available movies -->
        <div class="row">
            <?php
            $query = "SELECT * FROM movies";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($movie = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <img src="uploaded_img/<?php echo $movie['image']; ?>" class="card-img-top" alt="Movie Poster">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $movie['name']; ?></h5>
                                <p class="card-text"><?php echo $movie['details']; ?></p>
                                <p class="card-text">Release Date: <?php echo $movie['release_date']; ?></p>
                                <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary">View
                                    Details</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p class='text-center'>No movies available at the moment.</p>";
            }
            ?>
        </div>
    </div>

    <?php include('includes/footer.php'); // Include the footer 
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>