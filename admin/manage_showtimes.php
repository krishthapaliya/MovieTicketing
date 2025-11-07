<?php
// Include database connection and header
include('../includes/db_connect.php');
include('../includes/admin_header.php');

// Initialize variables for showtime data
$id = '';
$name = '';
$date = '';
$start_time = '';
$end_time = '';
$movie_id = '';
$price = '';  // Add price variable
$message = '';
$error = '';

// Store the selected date temporarily
if (isset($_POST['selected_date'])) {
    $selected_date = $_POST['selected_date'];
} else {
    $selected_date = date('Y-m-d'); // Default to today's date if no date is set
}

// Handle form submission for adding or editing showtimes
if (isset($_POST['save_showtime'])) {
    // Retrieve form data
    $name = $_POST['name'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $movie_id = $_POST['movie'];
    $price = $_POST['price']; // Get price from the form
    $id = $_POST['id'];

    // Validate required fields
    if (empty($name) || empty($date) || empty($start_time) || empty($end_time) || empty($movie_id) || empty($price)) {
        $error = "All fields are required.";
    } else {
        // Check for time collision
        $collision_check_query = "SELECT * FROM showtimes WHERE date = ? AND id != ? AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?)
        )";
        $stmt = $conn->prepare($collision_check_query);
        $stmt->bind_param("ssssss", $date, $id, $end_time, $start_time, $start_time, $start_time);
        $stmt->execute();
        $collision_result = $stmt->get_result();

        if ($collision_result->num_rows > 0) {
            $error = "Time collision detected. Please choose a different time.";
        } else {
            // Check if it's an update or insert operation
            if ($id != '') {
                // Update showtime
                $query = "UPDATE showtimes SET name = ?, date = ?, start_time = ?, end_time = ?, movie = ?, price = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssi", $name, $date, $start_time, $end_time, $movie_id, $price, $id);
                if ($stmt->execute()) {
                    $message = "Showtime updated successfully!";
                } else {
                    $error = "Error updating showtime: " . $stmt->error;
                }
            } else {
                // Insert new showtime
                $query = "INSERT INTO showtimes (name, date, start_time, end_time, movie, price) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssss", $name, $date, $start_time, $end_time, $movie_id, $price);
                if ($stmt->execute()) {
                    $message = "Showtime added successfully!";
                } else {
                    $error = "Error adding showtime: " . $stmt->error;
                }
            }
        }
    }
}

// Handle delete showtime
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM showtimes WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Showtime deleted successfully!";
    } else {
        $error = "Error deleting showtime: " . $stmt->error;
    }
}

// Fetch showtimes from the database for the selected date
$query = "SELECT st.*, m.name AS movie_name FROM showtimes st JOIN movies m ON st.movie = m.id WHERE st.date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$result = $stmt->get_result();

// Fetch movies for dropdown
$movies_query = "SELECT * FROM movies";
$movies_result = $conn->query($movies_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Showtimes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .date-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }

        .date-container input {
            width: 150px;
            /* Adjust the width as needed */
            margin-right: 10px;
            /* Space between input and button */
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Manage Showtimes</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php elseif ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="date-container">
            <form method="POST" action="manage_showtimes.php">
                <input type="date" name="selected_date" value="<?php echo $selected_date; ?>" required>
                <button type="submit" class="btn btn-primary">Get Showtimes</button>
            </form>
        </div>

        <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#showtimeModal">Add Showtime</button>

        <!-- Showtime List -->
        <h3>Showtime List for <?php echo $selected_date; ?></h3>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($showtime = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $showtime['name']; ?></h5>
                                <p class="card-text"><strong>Date:</strong> <?php echo $showtime['date']; ?></p>
                                <p class="card-text"><strong>Start Time:</strong> <?php echo $showtime['start_time']; ?></p>
                                <p class="card-text"><strong>End Time:</strong> <?php echo $showtime['end_time']; ?></p>
                                <p class="card-text"><strong>Movie:</strong> <?php echo $showtime['movie_name']; ?></p>
                                <p class="card-text"><strong>Price:</strong> <?php echo $showtime['price']; ?></p> <!-- Display price -->
                                <button class="btn btn-warning" data-toggle="modal" data-target="#showtimeModal"
                                    data-id="<?php echo $showtime['id']; ?>" data-name="<?php echo $showtime['name']; ?>"
                                    data-date="<?php echo $showtime['date']; ?>"
                                    data-start="<?php echo $showtime['start_time']; ?>"
                                    data-end="<?php echo $showtime['end_time']; ?>"
                                    data-movie="<?php echo $showtime['movie']; ?>"
                                    data-price="<?php echo $showtime['price']; ?>">Edit</button>
                                <a href="manage_showtimes.php?delete=<?php echo $showtime['id']; ?>" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this showtime?');">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-md-12">
                    <div class="alert alert-warning">No showtime for: <?php echo $selected_date; ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Showtime Modal -->
    <div class="modal fade" id="showtimeModal" tabindex="-1" role="dialog" aria-labelledby="showtimeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showtimeModalLabel">Add Showtime</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close                    ">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage_showtimes.php">
                        <input type="hidden" name="id" id="showtimeId" value="">
                        <div class="form-group">
                            <label for="showtimeName">Showtime Name</label>
                            <input type="text" class="form-control" name="name" id="showtimeName" required>
                        </div>
                        <div class="form-group">
                            <label for="showtimeDate">Date</label>
                            <input type="date" class="form-control" name="date" id="showtimeDate" required>
                        </div>
                        <div class="form-group">
                            <label for="showtimeStartTime">Start Time</label>
                            <input type="time" class="form-control" name="start_time" id="showtimeStartTime" required>
                        </div>
                        <div class="form-group">
                            <label for="showtimeEndTime">End Time</label>
                            <input type="time" class="form-control" name="end_time" id="showtimeEndTime" required>
                        </div>
                        <div class="form-group">
                            <label for="movieSelect">Movie</label>
                            <select class="form-control" name="movie" id="movieSelect" required>
                                <option value="">Select Movie</option>
                                <?php while ($movie = $movies_result->fetch_assoc()): ?>
                                    <option value="<?php echo $movie['id']; ?>"><?php echo $movie['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="showtimePrice">Price per Seat</label>
                            <input type="text" class="form-control" name="price" id="showtimePrice" required>
                        </div>
                        <button type="submit" name="save_showtime" class="btn btn-primary">Save Showtime</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $('#showtimeModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var id = button.data('id'); // Extract info from data-* attributes
            var name = button.data('name');
            var date = button.data('date');
            var start = button.data('start');
            var end = button.data('end');
            var movie = button.data('movie');
            var price = button.data('price');

            var modal = $(this);
            modal.find('#showtimeId').val(id);
            modal.find('#showtimeName').val(name);
            modal.find('#showtimeDate').val(date);
            modal.find('#showtimeStartTime').val(start);
            modal.find('#showtimeEndTime').val(end);
            modal.find('#movieSelect').val(movie);
            modal.find('#showtimePrice').val(price);
            if (id) {
                modal.find('.modal-title').text('Edit Showtime');
            } else {
                modal.find('.modal-title').text('Add Showtime');
                modal.find('form')[0].reset(); // Reset form for new entry
            }
        });
    </script>
</body>

</html>

