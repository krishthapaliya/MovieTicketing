<?php
// Include database connection and header
include('../includes/db_connect.php');
include('../includes/admin_header.php');

// Initialize variables for seat management
$message = '';
$error = '';

// Handle Add Row
if (isset($_POST['add_row'])) {
    $row_check = $conn->query("SELECT COUNT(DISTINCT seat_row) AS row_count FROM seats")->fetch_assoc();
    $col_check = $conn->query("SELECT COUNT(DISTINCT seat_column) AS col_count FROM seats")->fetch_assoc();

    $current_row_count = (int) $row_check['row_count'];
    $current_col_count = (int) $col_check['col_count'];

    if ($current_row_count == 0 && $current_col_count == 0) {
        $conn->query("INSERT INTO seats (seat_row, seat_column, name, status) VALUES ('A', 1, 'A1', 'available')");
        echo "<script>alert('Row and Column added with one seat');</script>";
    } else {
        $new_row_letter = chr(65 + $current_row_count);
        for ($i = 1; $i <= $current_col_count; $i++) {
            $seat_name = $new_row_letter . $i;
            $conn->query("INSERT INTO seats (seat_row, seat_column, name, status) VALUES ('$new_row_letter', $i, '$seat_name', 'available')");
        }
        echo "<script>alert('Row added successfully');</script>";
    }
    header("Location: manage_seats.php");
    exit;
}

// Handle Add Column
if (isset($_POST['add_column'])) {
    $row_check = $conn->query("SELECT COUNT(DISTINCT seat_row) AS row_count FROM seats")->fetch_assoc();
    $col_check = $conn->query("SELECT COUNT(DISTINCT seat_column) AS col_count FROM seats")->fetch_assoc();

    $current_row_count = (int) $row_check['row_count'];
    $current_col_count = (int) $col_check['col_count'];

    if ($current_row_count == 0 && $current_col_count == 0) {
        $conn->query("INSERT INTO seats (seat_row, seat_column, name, status) VALUES ('A', 1, 'A1', 'available')");
        echo "<script>alert('Row and Column added with one seat');</script>";
    } else {
        $new_col_number = $current_col_count + 1;
        $rows = $conn->query("SELECT DISTINCT seat_row FROM seats");
        while ($row = $rows->fetch_assoc()) {
            $seat_name = $row['seat_row'] . $new_col_number;
            $conn->query("INSERT INTO seats (seat_row, seat_column, name, status) VALUES ('" . $row['seat_row'] . "', $new_col_number, '$seat_name', 'available')");
        }
        echo "<script>alert('Column added successfully');</script>";
    }
    header("Location: manage_seats.php");
    exit;
}

// Handle removing the last row
if (isset($_POST['confirm_remove_last_row'])) {
    // Fetch the last row (by letter)
    $row_result = $conn->query("SELECT seat_row FROM seats ORDER BY seat_row DESC LIMIT 1");
    if ($row_result->num_rows > 0) {
        $last_row = $row_result->fetch_assoc()['seat_row'];
        $conn->query("DELETE FROM seats WHERE seat_row = '$last_row'");
        $message = "Last row '$last_row' removed successfully!";
    } else {
        $error = "No rows available to remove!";
    }
}

// Handle removing the last column
if (isset($_POST['confirm_remove_last_column'])) {
    // Fetch the last column (by number)
    $col_result = $conn->query("SELECT seat_column FROM seats ORDER BY seat_column DESC LIMIT 1");
    if ($col_result->num_rows > 0) {
        $last_col = $col_result->fetch_assoc()['seat_column'];
        $conn->query("DELETE FROM seats WHERE seat_column = $last_col");
        $message = "Last column '$last_col' removed successfully!";
    } else {
        $error = "No columns available to remove!";
    }
}

// Handle holding a seat
if (isset($_POST['hold_seat'])) {
    $seat_id = $_POST['seat_id'];
    $hold_date = $_POST['hold_date'];
    $showtime = $_POST['showtime'];

    $query = "UPDATE seats SET status = 'held', hold_date = ?, showtime = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $hold_date, $showtime, $seat_id);
    if ($stmt->execute()) {
        $message = "Seat held successfully!";
    } else {
        $error = "Cannot hold seat";
    }
}



// Fetch all seats from the database
$query = "SELECT * FROM seats ORDER BY seat_row, seat_column";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Seats</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .seat-layout {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-top: 20px;
        }

        .row-labels {
            display: flex;
            margin-bottom: 10px;
        }

        .row-label {
            width: 50px;
            text-align: center;
            font-weight: bold;
        }

        .seat-columns {
            display: flex;
        }

        .seat-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-right: 15px;
        }

        .seat-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 10px;
        }

        .seat {
            display: inline-block;
            margin: 5px;
            border: 1px solid #ccc;
            width: 50px;
            height: 50px;
            text-align: center;
            line-height: 50px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .seat-layout{
            margin-bottom: 20px;
            align-items: center;
        }

        .empty-seat {
            background-color: #e9ecef;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Manage Seats</h2>

        <!-- Alerts -->
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

        <!-- Seat management buttons -->
        <form method="POST" action="manage_seats.php">
            <button type="submit" class="btn btn-primary" name="add_row">Add Row</button>
            <button type="submit" class="btn btn-primary" name="add_column">Add Column</button>
            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#removeRowModal">Remove Last
                Row</button>
            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#removeColumnModal">Remove
                Last Column</button>
        </form>

        <!-- Seat Layout -->
        <h3 class="mt-5">Current Seat Layout</h3>
        <div class="seat-layout">
            <?php
            $result = $conn->query("SELECT * FROM seats ORDER BY seat_row, seat_column");
            $seats = [];

            while ($seat = $result->fetch_assoc()) {
                $seats[$seat['seat_row']][] = $seat;
            }

            if (!empty($seats)): ?>
                <div class="seat-columns">
                    <?php
                    $maxColumns = max(array_map('count', $seats)); ?>

                    <?php for ($i = 0; $i < $maxColumns; $i++): ?>
                        <div class="seat-column">
                            <?php foreach ($seats as $rowSeats): ?>
                                <div class="seat-card">
                                    <?php if (isset($rowSeats[$i])): ?>
                                        <div class="seat"><?php echo $rowSeats[$i]['name']; ?></div>
                                        <!-- <button type="button" class="btn btn-warning btn-sm mt-2" data-toggle="modal"
                                            data-target="#holdSeatModal" data-seat-id="<?php echo $rowSeats[$i]['id']; ?>">Hold
                                            Seat</button> -->
                                    <?php else: ?>
                                        <div class="seat empty-seat"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <p>No seats available.</p>
            <?php endif; ?>
        </div>

        <!-- Remove Row Modal -->
        <div class="modal fade" id="removeRowModal" tabindex="-1" role="dialog" aria-labelledby="removeRowModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="removeRowModalLabel">Remove Last Row</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete the last row?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="confirm_remove_last_row" class="btn btn-danger">Remove
                                Row</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Remove Column Modal -->
        <div class="modal fade" id="removeColumnModal" tabindex="-1" role="dialog"
            aria-labelledby="removeColumnModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="removeColumnModalLabel">Remove Last Column</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete the last column?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="confirm_remove_last_column" class="btn btn-danger">Remove
                                Column</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Hold Seat Modal -->
        <div class="modal fade" id="holdSeatModal" tabindex="-1" role="dialog" aria-labelledby="holdSeatModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="holdSeatModalLabel">Hold Seat</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="seat_id" id="seat_id" value="">
                            <div class="form-group">
                                <label for="hold_date">Hold Date:</label>
                                <input type="date" class="form-control" name="hold_date" id="hold_date" required>
                            </div>
                            <div class="form-group">
                                <label for="showtime">Showtime:</label>
                                <select class="form-control" name="showtime" id="showtime" required>
                                    <option value="">Select a showtime</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="hold_seat" class="btn btn-warning">Hold Seat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <?php include '../includes/admin_footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // When the modal is shown, set the seat_id value
        $('#holdSeatModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var seatId = button.data('seat-id'); // Extract seat ID from data attribute
            var modal = $(this);
            modal.find('#seat_id').val(seatId); // Set the seat ID in the hidden input
        });

        // You can also dynamically populate the showtime dropdown based on the selected date
        $('#hold_date').on('change', function () {
            var selectedDate = $(this).val();
            var showtimeDropdown = $('#showtime');

            // Clear previous options
            showtimeDropdown.empty();

            // Make an AJAX call to fetch showtimes based on the selected date
            $.ajax({
                url: 'fetch_showtimes.php', // Change this to your actual endpoint for fetching showtimes
                type: 'GET',
                data: { date: selectedDate },
                success: function (data) {
                    var showtimes = JSON.parse(data);
                    // Populate the showtime dropdown with the fetched showtimes
                    showtimes.forEach(function (showtime) {
                        showtimeDropdown.append($('<option>', {
                            value: showtime.id,
                            text: showtime.name + ' (' + showtime.start_time + ' - ' + showtime.end_time + ')'
                        }));
                    });
                }
            });
        });
    </script>

    </script>
</body>

</html>