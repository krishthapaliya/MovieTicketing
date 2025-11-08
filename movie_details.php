<?php
session_start();
include('includes/db_connect.php');
include('includes/header.php');

/**
 * Generates a pseudo-random hexadecimal UUID for transactions.
 * NOTE: For true cryptographic security in a production environment, 
 * consider using a dedicated library or database function.
 */
function generateRandomUuid() {
    return bin2hex(random_bytes(16));
}

// --- ⚙️ Process Booking POST Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booked_seats'])) {
    // Collect and sanitize POST data
    $movie_id = $_POST['movie_id'];
    $showtime_id = $_POST['showtime_id'];
    $user_id = $_SESSION['user_id'] ?? null; // Ensure user is logged in
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $booked_seats = trim($_POST['booked_seats']);
    $date = date('Y-m-d');
    $total_price = (float)$_POST['total_price'];
    $status = 'Pending'; // Set status to Pending before redirecting to payment
    $transaction_uuid = generateRandomUuid();

    if (!$user_id) {
        // Simple error handling for non-logged-in users trying to bypass the check
        echo "<div class='alert alert-danger'>You must be logged in to book tickets.</div>";
        exit;
    }

    // Insert booking into the database
    $booking_query = "INSERT INTO bookings (user_id, date, showtime, price, status, booked_seats, transaction_uuid) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($booking_query);
    // Use 'isidsss' for parameter binding: integer, string, integer, decimal (converted to string), string, string, string
    $stmt->bind_param("isidsss", $user_id, $date, $showtime_id, $total_price, $status, $booked_seats, $transaction_uuid);

    if ($stmt->execute()) {
        // Redirect to payment gateway (epay.php) with required parameters
        header("Location: epay.php?price=$total_price&transaction_uuid=$transaction_uuid&email=$email&movieid=$movie_id&showtimeid=$showtime_id&date=$date&seats=$booked_seats"); 
        exit();
    } else {
        echo "<div class='alert alert-danger'>Booking failed: " . htmlspecialchars($stmt->error) . ". Please try again.</div>";
        $stmt->close();
    }
}

// --- 📊 Fetch Movie Details and Showtimes ---
if (isset($_GET['id'])) {
    $movie_id = $_GET['id'];

    // 1. Fetch Movie details
    $movie_query = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($movie_query);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $movie_result = $stmt->get_result();
    $movie = $movie_result->fetch_assoc();
    if (!$movie) { echo "<p class='text-center'>Movie not found.</p>"; exit; }
    $stmt->close();

    // 2. Fetch Total Seats
    // NOTE: Switched to prepared statement for all queries for consistency
    $total_seats_query = "SELECT COUNT(*) AS total_seats FROM seats";
    $stmt = $conn->prepare($total_seats_query);
    $stmt->execute();
    $seats_data = $stmt->get_result()->fetch_assoc();
    $total_seats = $seats_data['total_seats'];
    $stmt->close();

    // 3. Fetch Seat Layout (Rows and Columns)
    $seats_query = "SELECT seat_row, seat_column FROM seats ORDER BY seat_row, seat_column";
    $stmt = $conn->prepare($seats_query);
    $stmt->execute();
    $seats_result = $stmt->get_result();
    $seats = [];
    while ($seat = $seats_result->fetch_assoc()) {
        $seats[$seat['seat_row']][] = $seat['seat_column'];
    }
    $stmt->close();

    // 4. Fetch Showtimes
    $showtime_query = "SELECT * FROM showtimes WHERE movie = ? AND date >= CURDATE() ORDER BY date, start_time";
    $stmt = $conn->prepare($showtime_query);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $showtime_result = $stmt->get_result();
    $showtimes = $showtime_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 5. Fetch User Details and Ratings
    $user_id = $_SESSION['user_id'] ?? null;
    $user = null;
    $user_rating = 0;

    if($user_id) {
        // User details for pre-filling form
        $user_query = "SELECT name, contact, email FROM user_detail WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user = $user_result->fetch_assoc();
        $stmt->close();

        // User rating
        $stmt = $conn->prepare("SELECT rating FROM reviews WHERE user_id=? AND movie_id=?");
        $stmt->bind_param("ii", $user_id, $movie_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows > 0) $user_rating = $res->fetch_assoc()['rating'];
        $stmt->close();
    }

    // 6. Average rating
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM reviews WHERE movie_id=?");
    $stmt->bind_param("i",$movie_id);
    $stmt->execute();
    $avg_result = $stmt->get_result()->fetch_assoc();
    $avg_rating = round($avg_result['avg_rating'],1);
    $total_ratings = $avg_result['total_ratings'];
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($movie['name']); ?> - Showtimes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/userstyle.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
<style>
/* 🎨 Dark Theme Styles */
body {
    background-color: #121212; 
    color: #e0e0e0; 
    font-family: 'Poppins', sans-serif;
}
.header-section {
    padding: 40px 0;
    margin-bottom: 20px;
    background: #1e1e1e;
    border-radius: 10px;
}
.imgcontainer {
    max-width: 300px;
    margin: auto;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.5);
}
.imgcontainer img{
    width: 100%;
    height: auto;
    display: block;
}
.details-box {
    padding: 20px;
    background: #1e1e1e;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}
.showtime-card {
    background: #2a2a2a;
    color: #fff;
    border: 1px solid #3e3e3e;
    transition: transform 0.2s;
}
.showtime-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.5);
}
.btn-primary {
    background-color: #e50914; /* Netflix Red */
    border: none;
}
/* Seat Styles */
.seat-map-container {
    background: #333;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    text-align: center;
}
.screen-area {
    width: 80%;
    margin: 0 auto 20px;
    padding: 10px;
    background: #555;
    color: #fff;
    border-radius: 5px;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(0,0,0,0.5);
}
.seat{
    display:inline-block;
    width:35px;
    height:35px;
    margin:3px;
    text-align:center;
    line-height:35px;
    border:2px solid #555; 
    cursor:pointer;
    border-radius:5px;
    font-size: 0.8rem;
    font-weight: 600;
}
.available{background-color:#007bff;color:#fff;border-color:#0056b3;} /* Blue for Available */
.booked{background-color:#dc3545;color:#fff;cursor:not-allowed;border-color:#a71d2a;} /* Red for Booked */
.selected{background-color:#ffc107;color:#000;border-color:#c89c0c;} /* Yellow for Selected */
.seat-row{
    margin-bottom:10px;
    padding: 0 10px;
    display: flex; 
    justify-content: center;
}
.note{display:flex;justify-content:center;gap:15px;margin-top:20px;}
.star{font-size:30px;color:#555;transition:color 0.2s;cursor:pointer;}
.star.checked,.star.hovered{color:#e50914;} /* Red stars */
</style>
</head>
<body>
<div class="container mt-5">
    
    <div class="header-section text-center">
        <h1 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($movie['name']); ?></h1>
        <p class="text-muted lead"><?php echo htmlspecialchars($movie['details']); ?></p>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="imgcontainer">
                <img src="uploaded_img/<?php echo urlencode($movie['image']); ?>" alt="<?php echo htmlspecialchars($movie['name']); ?>">
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="details-box">
                <h4 class="mb-3"><i class="fas fa-star text-warning me-2"></i> Ratings & Reviews</h4>
                <div class="d-flex align-items-center mb-3">
                    <h5 class="mb-0 me-3">Average Rating: <span id="avgRating" class="badge bg-warning text-dark fs-5"><?php echo $avg_rating; ?></span> / 5 </h5>
                    <span class="text-muted">(<?php echo $total_ratings; ?> ratings)</span>
                </div>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <div id="starRating" class="d-flex align-items-center">
                    <span class="me-3">Rate this movie:</span>
                    <?php for($i=1;$i<=5;$i++): ?>
                        <span class="star <?php echo ($i <= $user_rating)? 'checked' : ''; ?>" data-value="<?php echo $i; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <?php else: ?>
                    <p class="mt-3"><em><i class="fas fa-sign-in-alt me-1"></i> Login to rate this movie</em></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <h3 class="mt-5 mb-4 text-white border-bottom border-danger pb-2">📅 Available Showtimes</h3>
    
    <?php if(!empty($showtimes)): ?>
    <div class="row g-4">
        <?php foreach($showtimes as $showtime):
            // Fetch booked seats for the CURRENT showtime
            $bookings_query = "SELECT booked_seats FROM bookings WHERE showtime = ? AND status='Completed'"; 
            $stmt = $conn->prepare($bookings_query);
            $stmt->bind_param("i", $showtime['id']);
            $stmt->execute();
            $bookings_result = $stmt->get_result();
            $booked_seats_total = 0;
            $booked_seats = []; // Array of seat IDs (e.g., ['A1', 'A2'])

            while ($booking = $bookings_result->fetch_assoc()) {
                if (!empty($booking['booked_seats'])) {
                    $arr = explode(",", $booking['booked_seats']);
                    $booked_seats_total += count($arr);
                    $booked_seats = array_merge($booked_seats, $arr);
                }
            }
            $stmt->close();
            
            $available_seats = $total_seats - $booked_seats_total;
            if($available_seats > 0):
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card showtime-card h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-danger mb-2"><i class="far fa-calendar-alt me-2"></i> <?php echo $showtime['date']; ?></h5>
                    <p class="card-text flex-grow-1">
                        <span class="badge bg-secondary me-2"><i class="far fa-clock"></i> Start: <?php echo date('h:i A', strtotime($showtime['start_time'])); ?></span><br>
                        <span class="badge bg-secondary me-2 mt-1"><i class="far fa-clock"></i> End: <?php echo date('h:i A', strtotime($showtime['end_time'])); ?></span><br>
                        <strong class="text-info mt-2 d-block">Price: $<?php echo number_format($showtime['price'], 2); ?> per seat</strong>
                    </p>
                    <p class="mb-3">
                        <span class="text-success fw-bold"><?php echo $available_seats; ?></span> / 
                        <span class="text-light"><?php echo $total_seats; ?> Seats Available</span>
                    </p>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <button type="button" class="btn btn-primary mt-auto" data-bs-toggle="modal"
                                data-bs-target="#bookingModal" data-showtime-id="<?php echo $showtime['id']; ?>"
                                data-booked-seats="<?php echo implode(',', array_filter($booked_seats)); ?>"
                                data-price="<?php echo $showtime['price']; ?>">
                            <i class="fas fa-ticket-alt me-1"></i> Book Now
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary mt-auto" disabled><i class="fas fa-lock me-1"></i> Login to Book</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; endforeach; ?>
    </div>
    <?php else: ?>
        <div class="alert alert-secondary text-center mt-4">
            <i class="fas fa-frown me-2"></i> No upcoming showtimes available for this movie.
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-light">
        <form method="POST" action="movie_details.php?id=<?php echo $movie_id; ?>">
            <div class="modal-header border-danger">
                <h5 class="modal-title"><i class="fas fa-couch me-2"></i> Confirm Your Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="showtime_id" id="modalShowtimeId">
                <input type="hidden" name="pricePerSeat" id="pricePerSeat">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-danger border-bottom pb-2">Contact Details</h6>
                        <div class="mb-3"><label for="name" class="form-label">Full Name</label><input type="text" class="form-control bg-secondary text-light border-dark" id="name" name="name" required value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"></div>
                        <div class="mb-3"><label for="contact" class="form-label">Contact Number</label><input type="text" class="form-control bg-secondary text-light border-dark" id="contact" name="contact" required value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>"></div>
                        <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control bg-secondary text-light border-dark" id="email" name="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger border-bottom pb-2">Seat Selection</h6>
                        <div class="seat-map-container">
                            <div class="screen-area">SCREEN</div>
                            <div id="seatSelection">
                            <?php 
                            // Render ALL seats as 'available'. JS will update the status.
                            foreach ($seats as $row => $columns): ?>
                                <div class="seat-row"><strong><?php echo $row; ?></strong>
                                <?php foreach($columns as $column):
                                    $seat_id = $row.$column;
                                ?>
                                    <div class="seat available" data-seat-id="<?php echo $seat_id; ?>"><?php echo $column; ?></div>
                                <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            <div class="note">
                                <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Booked</span>
                                <span class="badge bg-primary"><i class="fas fa-check-circle"></i> Available</span>
                                <span class="badge bg-warning text-dark"><i class="fas fa-hand-pointer"></i> Selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="booked_seats" id="selectedSeats">
                <input type="hidden" name="total_price" id="total_price">

                <div class="alert alert-info mt-4 text-center">
                    <h4 class="mb-0">Total Price: <strong class="text-danger">$<span id="totalPriceDisplay">0.00</span></strong></h4>
                </div>

            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Proceed to Payment</button>
            </div>
        </form>
    </div>
</div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var selectedSeats = [];
var pricePerSeat = 0;

function updateTotalPrice() {
    var total = selectedSeats.length * pricePerSeat;
    $('#totalPriceDisplay').text(total.toFixed(2));
    $('#total_price').val(total.toFixed(2));
}

// Seat Click Handler
$(document).on('click', '.seat.available', function(){
    var $this = $(this);
    var seatId = $this.data('seat-id');

    // Only allow selection if the seat is currently available (not booked or selected)
    if (!$this.hasClass('booked')) {
        if($this.hasClass('selected')) {
            // Deselect
            $this.removeClass('selected');
            selectedSeats = selectedSeats.filter(s => s !== seatId);
        } else {
            // Select
            $this.addClass('selected');
            selectedSeats.push(seatId);
        }
        updateTotalPrice();
    }
});

// Modal Open Handler: Initializes seats based on selected showtime
$('#bookingModal').on('show.bs.modal', function(event){
    var button = $(event.relatedTarget);
    var showtimeId = button.data('showtime-id');
    var bookedSeats = button.data('booked-seats').split(',').filter(s => s.trim() !== ""); 
    pricePerSeat = parseFloat(button.data('price')); // Ensure price is a float

    $('#modalShowtimeId').val(showtimeId);
    $('#pricePerSeat').val(pricePerSeat.toFixed(2)); 
    selectedSeats = [];
    updateTotalPrice(); 

    // Reset and apply booked status dynamically
    $('.seat').each(function(){
        var seatElement = $(this);
        var seatId = seatElement.data('seat-id');
        
        // 1. Reset all seat classes
        seatElement.removeClass('booked selected').addClass('available');
        seatElement.css('cursor', 'pointer'); // Reset cursor

        // 2. Check if this seat is booked for the selected showtime
        if(bookedSeats.includes(seatId)) {
            // If booked
            seatElement.removeClass('available').addClass('booked');
            seatElement.css('cursor', 'not-allowed');
        } 
    });
});

// Form Submission: Checks if seats are selected and prepares the list
$('form').on('submit', function(e){
    if (selectedSeats.length === 0) {
        alert("Please select at least one seat before proceeding.");
        e.preventDefault();
        return;
    }
    $('#selectedSeats').val(selectedSeats.join(','));
});

// Rating AJAX 
$('#starRating .star').on('click', function() {
    var rating = $(this).data('value');
    var movie_id = <?php echo $movie_id; ?>; 

    $.ajax({
        url: 'submit_review.php', // This file should handle the database insertion/update
        method: 'POST',
        data: { movie_id: movie_id, rating: rating },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                $('#avgRating').text(res.avg_rating);
                $('#starRating .star').each(function() {
                    $(this).toggleClass('checked', $(this).data('value') <= rating);
                });
            } else {
                alert(res.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
});

</script>
</body>
</html>