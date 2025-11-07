<?php
include('../includes/db_connect.php'); // Include the database connection
include('../includes/header.php'); // Include the header

// Fetch all users from the user_detail table
$user_query = "SELECT * FROM user_detail";
$user_result = mysqli_query($conn, $user_query);
$users = mysqli_fetch_all($user_result, MYSQLI_ASSOC);

// Process user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Delete user from user_detail table
    $delete_query = "DELETE FROM user_detail WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>User deleted successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>Failed to delete user. Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Manage Users</h2>

        <div class="row mt-4">
            <?php foreach ($users as $user): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $user['username']; ?></h5>
                            <p class="card-text">
                                <strong>Email:</strong> <?php echo $user['email']; ?><br>
                                <strong>Contact:</strong> <?php echo $user['contact']; ?>
                            </p>
                            <a href="?delete_id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include('../includes/footer.php'); // Include the footer ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
