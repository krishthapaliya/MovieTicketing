<?php
// Include necessary files
include '../includes/admin_header.php';
include '../includes/db_connect.php';

// Fetch booking data along with user info and showtime details
$sql = "SELECT bookings.id, user_detail.username, user_detail.email, user_detail.contact, bookings.date, bookings.showtime, bookings.price, bookings.status, bookings.booked_seats
        FROM bookings
        JOIN user_detail ON bookings.user_id = user_detail.id
        JOIN showtimes ON bookings.showtime = showtimes.id
        ORDER BY bookings.date DESC";
$result = mysqli_query($conn, $sql);


if (isset($_POST['delete-booking'])) {
    $user_id = $_POST['user_id'];
    
    // Call your delete_user function here
    $message = delete_user($conn, $user_id);
    
    // After deletion, you can redirect or show a message
    echo $message;
}

function delete_user($conn, $user_id)
{
    // Sanitize the user ID to prevent SQL injection
    $user_id = mysqli_real_escape_string($conn, $user_id);

    // SQL query to delete the user from the 'bookings' table
    $sql = "DELETE FROM bookings WHERE id = $user_id";

    // Execute the query
    if (mysqli_query($conn, $sql)) {
        // Check if any rows were affected
        if (mysqli_affected_rows($conn) > 0) {
            return "User successfully deleted.";
        } else {
            return "No user found with the given ID.";
        }
    } else {
        // Return error if query failed
        return "Error deleting user: " . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <!-- Custom CSS for booked and available seats -->
    <style>
        .booked-seat {
            background-color: #ff6b6b;
            color: white;
            font-weight: bold;
        }

        .available-seat {
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }

        .table td,
        .table th {
            vertical-align: middle;
            text-align: center;
        }

        .btn-info {
            background-color: #17a2b8;
            border: none;
        }

        .btn-info:hover {
            background-color: #138496;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Manage Bookings</h2>

        <!-- Display table of bookings -->
        <?php if (mysqli_num_rows($result) > 0) { ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Showtime</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Booked Seats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr style="background-color:rgba(87, 85, 86, 0.8); color:white;">
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['showtime']; ?></td>
                            <td><?php echo $row['price']; ?></td>
                            <td class="<?php echo $row['status'] == 'booked' ? 'booked-seat' : 'available-seat'; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </td>
                            <td><?php echo $row['booked_seats']; ?></td>
                            <td>
                                <!-- View user info button -->
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#viewUserModal<?php echo $row['id']; ?>">
                                    View User
                                </button>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete-booking" class="btn btn-info btn-sm">Delete Booking</button>
                                </form>

                            </td>
                        </tr>

                        <!-- User Info Modal -->
                        <div class="modal fade" id="viewUserModal<?php echo $row['id']; ?>" tabindex="-1"
                            aria-labelledby="viewUserModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewUserModalLabel">User Information</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Username:</strong> <?php echo $row['username']; ?></p>
                                        <p><strong>Email:</strong> <?php echo $row['email']; ?></p>
                                        <p><strong>Contact:</strong> <?php echo $row['contact']; ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="text-center">No bookings found.</p>
        <?php } ?>
    </div>
    <?php include '../includes/admin_footer.php' ?>
    <!-- Bootstrap JS (required for modal) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>

</html>