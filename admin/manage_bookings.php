<?php
include '../includes/admin_header.php';
include '../includes/db_connect.php';

// --- Delete Booking Function ---
function delete_booking_by_id($conn, $booking_id) {
    $sql = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            return "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        Booking ID: $booking_id successfully deleted.
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button>
                    </div>";
        } else {
            return "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                        No booking found with ID: $booking_id.
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button>
                    </div>";
        }
    } else {
        return "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    Error deleting booking: " . $stmt->error . "
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button>
                </div>";
    }
}

$message = '';
if (isset($_POST['delete-booking'])) {
    $booking_id = $_POST['booking_id'];
    $message = delete_booking_by_id($conn, $booking_id);
}

// Fetch bookings with user and showtime info
$sql = "SELECT b.id, u.username, u.email, u.contact, b.date, b.showtime, b.price, b.status, b.booked_seats
        FROM bookings b
        JOIN user_detail u ON b.user_id = u.id
        JOIN showtimes s ON b.showtime = s.id
        ORDER BY b.date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Bookings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
body { background-color: #121212; color: #e0e0e0; }
.navbar-dark .navbar-brand { color: #0d6efd; font-weight: 600; }
.card, .table { background-color: rgba(33,33,33,0.9); box-shadow: 0 4px 12px rgba(0,0,0,0.5); }
.table thead th { border-bottom: 1px solid #444; }
.badge-booked { background-color: #dc3545; }
.badge-available { background-color: #198754; }
.btn-sm:hover { opacity: 0.85; }
.modal-content.dark-modal { background-color: #1e1e1e; color: #f8f9fa; }
</style>
</head>
<body>


<div class="container mt-5">
    <?php echo $message; ?>
    <h2 class="text-center text-info mb-4"><i class="fas fa-ticket-alt me-2"></i>Manage Bookings</h2>

    <div class="table-responsive">
    <?php if(mysqli_num_rows($result) > 0) { ?>
        <table class="table table-bordered table-hover align-middle text-center text-white">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Showtime ID</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Seats</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['showtime']); ?></td>
                    <td><?php echo htmlspecialchars($row['price']); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status']=='booked'?'badge-booked':'badge-available'; ?>">
                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['booked_seats']); ?></td>
                    <td>
                        <button class="btn btn-light btn-sm me-2" data-bs-toggle="modal"
                            data-bs-target="#viewUserModal<?php echo $row['id']; ?>">View User</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete-booking" class="btn btn-danger btn-sm"
                            onclick="return confirm('⚠️ Delete Booking ID: <?php echo $row['id']; ?>?');">
                            Delete</button>
                        </form>
                    </td>
                </tr>

                <!-- User Info Modal -->
                <div class="modal fade" id="viewUserModal<?php echo $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content dark-modal">
                            <div class="modal-header border-secondary">
                                <h5 class="modal-title">User Information</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($row['username']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['contact']); ?></p>
                            </div>
                            <div class="modal-footer border-secondary">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <div class="alert alert-info text-center mt-5">No bookings found.</div>
    <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
