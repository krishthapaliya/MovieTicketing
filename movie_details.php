
<?php
// error_reporting(0);
include('includes/db_connect.php'); // Include the database connection
include('includes/header.php'); // Include the header
function generateRandomUuid() {
    return bin2hex(random_bytes(16));
}

// Process the booking if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booked_seats'])) {
    $movie_id = $_POST['movie_id'];
    $showtime_id = $_POST['showtime_id'];
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $booked_seats = $_POST['booked_seats']; // Seat numbers as comma-separated string
    $date = date('Y-m-d');
    $pricePerSeat = $_POST['pricePerSeat'];
    $total_price = $_POST['total_price'];
    $status = 'Completed';
    $transaction_uuid = generateRandomUuid(); // Use the UUID function

    // Insert the booking '
    $booking_query = "INSERT INTO bookings (user_id, date, showtime, price, status, booked_seats, transaction_uuid) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($booking_query);
    $stmt->bind_param("isissss", $user_id, $date, $showtime_id, $total_price, $status, $booked_seats, $transaction_uuid);

    if ($stmt->execute()) {
        // Redirect to payment page
        header("Location: epay.php?price=$total_price&transaction_uuid=$transaction_uuid&email=$email&movieid=$movie_id&showtimeid=$showtime_id&date=$date&seats=$booked_seats"); 
        exit();
    } else {
        echo "<div class='alert alert-danger'>Booking failed. Please try again.</div>";
    }
}

// Fetch movie details based on the movie id
if (isset($_GET['id'])) {
    $movie_id = $_GET['id'];
    // Fetch movie details from the movies table
    $movie_query = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($movie_query);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $movie_result = $stmt->get_result();
    $movie = $movie_result->fetch_assoc();

    if (!$movie) {
        echo "<p class='text-center'>Movie not found.</p>";
        exit();
    }

    // Fetch total seats by row and column from the seats table
    $total_seats_query = "SELECT COUNT(*) AS total_seats FROM seats";
    $total_seats_result = mysqli_query($conn, $total_seats_query);
    $seats_data = mysqli_fetch_assoc($total_seats_result);
    $total_seats = $seats_data['total_seats'];

    $seats_query = "SELECT seat_row, seat_column FROM seats ORDER BY seat_row, seat_column";
    $seats_result = mysqli_query($conn, $seats_query);
    $seats = [];
    while ($seat = mysqli_fetch_assoc($seats_result)) {
        $seats[$seat['seat_row']][] = $seat['seat_column'];
    }

    // Fetch available showtimes for the movie
    $showtime_query = "SELECT * FROM showtimes WHERE movie = ? AND date >= CURDATE() ORDER BY date, start_time";
    $stmt = $conn->prepare($showtime_query);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $showtime_result = $stmt->get_result();
    $showtimes = $showtime_result->fetch_all(MYSQLI_ASSOC);

    // Fetch current user's details (assuming user is logged in and user_id is available in session)
    // $user_id = $_SESSION['user_id']; // This should be set when the user logs in
    $user_query = "SELECT * FROM user_detail WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movie['name']; ?> - Showtimes</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/userstyle.css">
    <style>
        .imgcontainer img{
            max-width: 250px;
            margin-left: 40%;
        }
        .imgcontainer{
            align-self: center;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center"><?php echo $movie['name']; ?></h2>
        <p class="text-center"><?php echo $movie['details']; ?></p>
        <div class="imgcontainer">
            
        
            <img src="uploaded_img/<?php echo $movie['image']; ?>">
        </div>

        <h3 class="mt-4">Available Showtimes</h3>

        <?php if (!empty($showtimes)): ?>
            <div class="row">
                <?php
                foreach ($showtimes as $showtime) {
                    // Fetch the number of booked seats for the current showtime
                    $bookings_query = "SELECT booked_seats FROM bookings WHERE showtime = ?";
                    $stmt = $conn->prepare($bookings_query);
                    $stmt->bind_param("i", $showtime['id']);
                    $stmt->execute();
                    $bookings_result = $stmt->get_result();
                    $booked_seats_total = 0;
                    $booked_seats = [];

                    while ($booking = $bookings_result->fetch_assoc()) {
                        $booked_seats_array = explode(",", $booking['booked_seats']);
                        $booked_seats_total += count($booked_seats_array);
                        $booked_seats = array_merge($booked_seats, $booked_seats_array);
                    }

                    // Calculate available seats
                    $available_seats = $total_seats - $booked_seats_total;

                    // Display showtime details
                    if ($available_seats > 0) {
                ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Showtime on <?php echo $showtime['date']; ?></h5>
                                    <p class="card-text">
                                        Start Time: <?php echo $showtime['start_time']; ?><br>
                                        End Time: <?php echo $showtime['end_time']; ?><br>
                                        Available Seats: <?php echo $available_seats, "/", $total_seats; ?>
                                    </p>
                                    <!-- Trigger modal -->
                                    <?php if (isset($_SESSION['user_id'])): // Check if user is logged in 
                                    ?>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#bookingModal" data-showtime-id="<?php echo $showtime['id']; ?>"
                                            data-booked-seats="<?php echo implode(',', $booked_seats); ?>"
                                            data-price="<?php echo $showtime['price']; ?>">
                                            Book Now
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary" disabled>
                                            Login to Book
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                }
                ?>
            </div>
        <?php else: ?>
            <p class="text-center">No upcoming showtimes available for this movie.</p>
        <?php endif; ?>
    </div>

    <!-- Booking Modal -->

    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="movie_details.php?id=<?php echo $movie_id; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bookingModalLabel">Book Your Seat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="showtime_id" id="modalShowtimeId">
                        <input type="hidden" name="pricePerSeat" id="pricePerSeat">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                  required>
                        </div>
                        <div class="mb-3">
                            <label for="contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact" name="contact"
                                 required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                 required>
                        </div>
                        <div class="mb-3">
                            <label for="seats" class="form-label">Select Seats</label>
                            <div id="seatSelection">
                                <?php foreach ($seats as $row => $columns): ?>
                                    <div class="seat-row">
                                        <strong><?php echo $row; ?></strong>
                                        <?php foreach ($columns as $column):
                                            $seat_id = $row . $column;
                                            $is_booked = in_array($seat_id, $booked_seats);
                                        ?>
                                            <div class="seat <?php echo $is_booked ? 'booked' : 'available'; ?>"
                                                data-seat-id="<?php echo $seat_id; ?>">
                                                <?php echo $column; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <input type="hidden" name="movie_id" id="movie_id" value="<?php echo $movie_id; ?>">
                        <input type="hidden" name="booked_seats" id="selectedSeats">
                        <input type="hidden" name="total_price" id="total_price">
                        <div class="mb-3">
                            <label for="totalPrice" class="form-label">Total Price</label>
                            <input type="text" class="form-control" id="totalPrice" value="0" readonly>
                        </div>
                    </div>
                    <div class="note mb-3">
                        <span class="badge bg-danger">Booked</span>
                        <span class="badge bg-success">Available</span>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); // Include the footer 
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Modal seat selection and booking functionality
        var selectedSeats = [];
        var pricePerSeat;

        function updateTotalPrice() {
            var totalPrice = selectedSeats.length * pricePerSeat;
            document.getElementById('totalPrice').value = totalPrice;
            document.getElementById('total_price').value = totalPrice; // Update the hidden total price
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('available')) {
                e.target.classList.toggle('selected');
                var seatId = e.target.getAttribute('data-seat-id');
                if (e.target.classList.contains('selected')) {
                    selectedSeats.push(seatId);
                } else {
                    selectedSeats = selectedSeats.filter(s => s !== seatId);
                }
                // Update total price whenever the selection changes
                updateTotalPrice();
            }
        });

        var bookingModal = document.getElementById('bookingModal');
        bookingModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var showtimeId = button.getAttribute('data-showtime-id');
            var bookedSeats = button.getAttribute('data-booked-seats');
            pricePerSeat = button.getAttribute('data-price'); // Get the price for the showtime

            var modalShowtimeId = document.getElementById('modalShowtimeId');
            modalShowtimeId.value = showtimeId;

            // Reset selected seats
            selectedSeats = [];

            // Mark booked seats as unavailable
            document.querySelectorAll('.seat').forEach(function(seat) {
                seat.classList.remove('booked', 'selected');
                var seatId = seat.getAttribute('data-seat-id');
                if (bookedSeats.split(',').includes(seatId)) {
                    seat.classList.add('booked');
                }
            });
        });

        // Submit booked seats with the form
        document.querySelector('form').addEventListener('submit', function(e) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'booked_seats';
            input.value = selectedSeats.join(',');
            this.appendChild(input);
        });
    </script>

    <style>
        .seat {
            display: inline-block;
            width: 30px;
            height: 30px;
            margin: 2px;
            text-align: center;
            line-height: 30px;
            border: 1px solid #ccc;
            cursor: pointer;
        }

        .available {
            background-color: #28a745;
        }

        .booked {
            background-color: #dc3545;
            cursor: not-allowed;
        }

        .selected {
            background-color: #ffc107;
        }

        .seat-row {
            margin-bottom: 10px;
        }

        .note {
            align-items: center;
            margin-left: 30%;
        }
    </style>
</body>

</html>