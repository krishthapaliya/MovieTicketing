<?php
// Include database connection and header
include('../includes/db_connect.php');
include('../includes/admin_header.php');

// Initialize variables for seat management
$message = '';
$error = '';

// --- Utility Functions (Refactored for security) ---

// Handle Add Row
if (isset($_POST['add_row'])) {
    // NOTE: In production, consider wrapping these operations in a transaction.
    $row_check = $conn->query("SELECT COUNT(DISTINCT seat_row) AS row_count FROM seats")->fetch_assoc();
    $col_check = $conn->query("SELECT COUNT(DISTINCT seat_column) AS col_count FROM seats")->fetch_assoc();

    $current_row_count = (int) $row_check['row_count'];
    $current_col_count = (int) $col_check['col_count'];

    if ($current_row_count == 0 || $current_col_count == 0) {
        // If empty, start with A1
        $conn->query("INSERT INTO seats (seat_row, seat_column, name, status) VALUES ('A', 1, 'A1', 'available')");
        $message = "Created starting row 'A' and column '1'.";
    } else {
        $new_row_letter = chr(65 + $current_row_count);
        $stmt = $conn->prepare("INSERT INTO seats (seat_row, seat_column, name, status) VALUES (?, ?, ?, 'available')");

        for ($i = 1; $i <= $current_col_count; $i++) {
            $seat_name = $new_row_letter . $i;
            $stmt->bind_param("sis", $new_row_letter, $i, $seat_name);
            $stmt->execute();
        }
        $stmt->close();
        $message = "Row '{$new_row_letter}' added successfully.";
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

    if ($current_row_count == 0 || $current_col_count == 0) {
        $conn->query("INSERT INTO seats (seat_row, seat_column, name, status) VALUES ('A', 1, 'A1', 'available')");
        $message = "Created starting row 'A' and column '1'.";
    } else {
        $new_col_number = $current_col_count + 1;
        $rows = $conn->query("SELECT DISTINCT seat_row FROM seats");
        $stmt = $conn->prepare("INSERT INTO seats (seat_row, seat_column, name, status) VALUES (?, ?, ?, 'available')");

        while ($row = $rows->fetch_assoc()) {
            $seat_name = $row['seat_row'] . $new_col_number;
            $stmt->bind_param("sis", $row['seat_row'], $new_col_number, $seat_name);
            $stmt->execute();
        }
        $stmt->close();
        $message = "Column '{$new_col_number}' added successfully.";
    }
    header("Location: manage_seats.php");
    exit;
}

// Handle removing the last row (Used Prepared Statement)
if (isset($_POST['confirm_remove_last_row'])) {
    $row_result = $conn->query("SELECT seat_row FROM seats ORDER BY seat_row DESC LIMIT 1");
    if ($row_result->num_rows > 0) {
        $last_row = $row_result->fetch_assoc()['seat_row'];
        
        $stmt = $conn->prepare("DELETE FROM seats WHERE seat_row = ?");
        $stmt->bind_param("s", $last_row);
        $stmt->execute();

        $message = "Last row '$last_row' removed successfully!";
    } else {
        $error = "No rows available to remove!";
    }
}

// Handle removing the last column (Used Prepared Statement)
if (isset($_POST['confirm_remove_last_column'])) {
    $col_result = $conn->query("SELECT seat_column FROM seats ORDER BY seat_column DESC LIMIT 1");
    if ($col_result->num_rows > 0) {
        $last_col = $col_result->fetch_assoc()['seat_column'];
        
        $stmt = $conn->prepare("DELETE FROM seats WHERE seat_column = ?");
        $stmt->bind_param("i", $last_col);
        $stmt->execute();
        
        $message = "Last column '$last_col' removed successfully!";
    } else {
        $error = "No columns available to remove!";
    }
}

// Handle holding a seat (Already used Prepared Statement - good job!)
if (isset($_POST['hold_seat'])) {
    $seat_id = $_POST['seat_id'];
    $hold_date = $_POST['hold_date'];
    $showtime = $_POST['showtime'];

    $query = "UPDATE seats SET status = 'held', hold_date = ?, showtime = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    // Assuming showtime is an integer (i)
    $stmt->bind_param("sii", $hold_date, $showtime, $seat_id); 
    if ($stmt->execute()) {
        $message = "Seat held successfully!";
    } else {
        $error = "Cannot hold seat: " . $stmt->error;
    }
}


// --- Data Fetching for Display ---

// Fetch all seats and group them by row for easier rendering
$query = "SELECT * FROM seats ORDER BY seat_row ASC, seat_column ASC";
$result = $conn->query($query);

$seats_by_row = [];
$column_numbers = [];
while ($seat = $result->fetch_assoc()) {
    $seats_by_row[$seat['seat_row']][$seat['seat_column']] = $seat;
    if (!in_array($seat['seat_column'], $column_numbers)) {
        $column_numbers[] = $seat['seat_column'];
    }
}
sort($column_numbers); // Ensure columns are numerically sorted

// Fetch showtimes for the "Hold Seat" modal (simplified fetch)
$showtimes_query = "SELECT id, name FROM showtimes WHERE date >= CURDATE() ORDER BY date, name";
$showtimes_result = $conn->query($showtimes_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Seats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Dark theme-friendly seat styles */
        body {
            background-color: #212529; /* Dark background */
            color: #f8f9fa; /* Light text */
        }
        
        .screen {
            background-color: #6c757d; /* Gray screen */
            color: #fff;
            padding: 10px;
            margin: 20px auto;
            text-align: center;
            width: 80%;
            border-radius: 5px;
            font-weight: bold;
        }

        .seat-layout-grid {
            overflow-x: auto; /* Enable horizontal scrolling for many seats */
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center the entire seat block */
        }
        
        .seat-row {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .row-label {
            font-weight: bold;
            margin-right: 15px;
            width: 25px;
            text-align: center;
        }

        .seat-button {
            width: 45px;
            height: 45px;
            margin: 3px;
            font-size: 0.75rem;
            line-height: 1;
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #fff; /* White text on buttons */
        }

        /* Color classes for seat status */
        .seat-available {
            background-color: #198754; /* Success Green */
        }
        .seat-held {
            background-color: #ffc107; /* Warning Yellow */
            color: #212529 !important; /* Dark text on yellow */
        }
        .seat-booked {
            background-color: #dc3545; /* Danger Red */
        }
        
        .seat-status-legend .badge {
            margin-right: 10px;
        }
    </style>
</head>

<body class="bg-dark text-white">
    <div class="container mt-5">
        <h2 class="text-center text-light mb-4">Manage Seats</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <form method="POST" action="manage_seats.php" class="me-2">
                <button type="submit" class="btn btn-success" name="add_row">Add Row</button>
            </form>
            <form method="POST" action="manage_seats.php" class="me-4">
                <button type="submit" class="btn btn-success" name="add_column">Add Column</button>
            </form>

            <button type="button" class="btn btn-outline-danger me-2" data-bs-toggle="modal" data-bs-target="#removeRowModal">
                Remove Last Row
            </button>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#removeColumnModal">
                Remove Last Column
            </button>
        </div>
        
        <div class="seat-status-legend mb-3">
            <span class="badge bg-success">Available</span>
            <span class="badge bg-warning text-dark">Held</span>
            <span class="badge bg-danger">Booked (Simulated)</span>
        </div>


        <h3 class="mt-5 text-light">Current Seat Layout</h3>
        <div class="screen">SCREEN THIS WAY</div>

        <div class="seat-layout-grid shadow p-3 rounded bg-dark border border-secondary">
            <?php if (!empty($seats_by_row)): ?>
                <div class="seat-row mb-2">
                    <div class="row-label"></div> <?php foreach ($column_numbers as $colNum): ?>
                        <div class="seat-button bg-secondary text-center"><?php echo $colNum; ?></div>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($seats_by_row as $rowLetter => $rowSeats): ?>
                    <div class="seat-row">
                        <div class="row-label bg-secondary rounded-start text-center me-2 py-2"><?php echo $rowLetter; ?></div>
                        <?php foreach ($column_numbers as $colNum): ?>
                            <?php 
                                $seat = $rowSeats[$colNum] ?? null;
                                $status_class = 'seat-available'; // Default to available
                                $seat_name = '';
                                $seat_id = '';
                                $is_available = false;
                                
                                if ($seat) {
                                    $seat_name = htmlspecialchars($seat['name']);
                                    $seat_id = htmlspecialchars($seat['id']);
                                    switch ($seat['status']) {
                                        case 'held':
                                            $status_class = 'seat-held';
                                            break;
                                        case 'booked': // Assuming 'booked' is another status
                                            $status_class = 'seat-booked';
                                            break;
                                        case 'available':
                                        default:
                                            $status_class = 'seat-available';
                                            $is_available = true;
                                            break;
                                    }
                                } else {
                                    // Handle missing seats in the grid (shouldn't happen with matrix approach)
                                    $status_class = 'bg-secondary';
                                    $seat_name = 'N/A';
                                }
                            ?>
                            <button type="button" 
                                class="btn seat-button <?php echo $status_class; ?>" 
                                <?php if ($seat && $is_available): ?>
                                    data-bs-toggle="modal" 
                                    data-bs-target="#holdSeatModal" 
                                    data-seat-id="<?php echo $seat_id; ?>"
                                <?php endif; ?>
                                title="Seat: <?php echo $seat_name; ?> | Status: <?php echo ucfirst($seat['status'] ?? 'N/A'); ?>"
                                <?php if (!$seat): ?> disabled <?php endif; ?>
                                >
                                <?php echo $colNum; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info w-100 text-center">
                    <p class="mb-0">No seats available. Click "Add Row" or "Add Column" to begin building the layout.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="screen-spacer my-5"></div>

        <div class="modal fade" id="removeRowModal" tabindex="-1" aria-labelledby="removeRowModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-white">
                    <form method="POST">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="removeRowModalLabel">Remove Last Row</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to **permanently delete** the last row of seats?
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="confirm_remove_last_row" class="btn btn-danger">Remove Row</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="removeColumnModal" tabindex="-1" aria-labelledby="removeColumnModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-white">
                    <form method="POST">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="removeColumnModalLabel">Remove Last Column</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to **permanently delete** the last column of seats?
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="confirm_remove_last_column" class="btn btn-danger">Remove Column</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="holdSeatModal" tabindex="-1" aria-labelledby="holdSeatModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-white">
                    <form method="POST">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="holdSeatModalLabel">Hold Seat</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="seat_id" id="seat_id" value="">
                            <p>You are holding seat: <strong id="seat_name_display"></strong></p>
                            
                            <div class="mb-3">
                                <label for="hold_date" class="form-label">Hold Date:</label>
                                <input type="date" class="form-control bg-secondary text-white border-0" name="hold_date" id="hold_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="showtime" class="form-label">Showtime:</label>
                                <select class="form-select bg-secondary text-white border-0" name="showtime" id="showtime" required>
                                    <option value="">Select a showtime</option>
                                    <?php 
                                    // Rewind result pointer if needed (though it shouldn't be necessary if fetch is done once)
                                    if ($showtimes_result && $showtimes_result->num_rows > 0) {
                                        $showtimes_result->data_seek(0);
                                        while ($st = $showtimes_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($st['id']); ?>"><?php echo htmlspecialchars($st['name']); ?></option>
                                        <?php endwhile; 
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="hold_seat" class="btn btn-warning text-dark">Hold Seat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    
    <?php include '../includes/admin_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Update the modal handler to use Bootstrap 5 events (show.bs.modal) and get the seat name
        var holdSeatModal = document.getElementById('holdSeatModal');
        holdSeatModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var seatId = button.getAttribute('data-seat-id'); 
            var seatName = button.textContent.trim(); // Get the seat name (A1, B2, etc.) from the button text/title

            // Set the seat ID and display name in the modal
            var modalSeatIdInput = holdSeatModal.querySelector('#seat_id');
            var modalSeatNameDisplay = holdSeatModal.querySelector('#seat_name_display');

            modalSeatIdInput.value = seatId;
            // Use the button's title for a more reliable name display
            modalSeatNameDisplay.textContent = button.getAttribute('title').split(' | ')[0].replace('Seat: ', ''); 

            // Clear previous date/showtime selections
            holdSeatModal.querySelector('#hold_date').value = '';
            holdSeatModal.querySelector('#showtime').innerHTML = '<option value="">Select a showtime</option>';
        });

        // Retained and simplified AJAX logic for showtime update
        $('#hold_date').on('change', function () {
            var selectedDate = $(this).val();
            var showtimeDropdown = $('#showtime');

            showtimeDropdown.empty().append($('<option>', {
                value: '',
                text: 'Loading showtimes...'
            }));

            // Make an AJAX call to fetch showtimes based on the selected date
            $.ajax({
                url: 'fetch_showtimes.php', // This file must exist and handle the AJAX request
                type: 'GET',
                data: { date: selectedDate },
                success: function (data) {
                    showtimeDropdown.empty().append($('<option>', { value: '', text: 'Select a showtime' }));
                    try {
                        var showtimes = JSON.parse(data);
                        showtimes.forEach(function (showtime) {
                            showtimeDropdown.append($('<option>', {
                                value: showtime.id,
                                text: showtime.name + ' (' + showtime.start_time + ' - ' + showtime.end_time + ')'
                            }));
                        });
                    } catch (e) {
                        showtimeDropdown.empty().append($('<option>', { value: '', text: 'No showtimes found for this date.' }));
                        console.error("Error parsing JSON:", e, data);
                    }
                },
                error: function() {
                     showtimeDropdown.empty().append($('<option>', { value: '', text: 'Error fetching showtimes.' }));
                }
            });
        });
    </script>
</body>

</html>