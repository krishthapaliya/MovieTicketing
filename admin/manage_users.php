<?php
include('../includes/db_connect.php'); // Database connection
include('../includes/header.php'); // Header (optional, can remove if using custom navbar)

// Fetch all users
$user_query = "SELECT * FROM user_detail";
$user_result = mysqli_query($conn, $user_query);
$users = mysqli_fetch_all($user_result, MYSQLI_ASSOC);

// Process user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM user_detail WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>User deleted successfully.<button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button></div>";
    } else {
        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Failed to delete user. Please try again.<button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Dark Theme</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
body {
    background-color: #121212;
    color: #e0e0e0;
}
.navbar-dark .navbar-nav .nav-link {
    color: #e0e0e0;
}
.navbar-dark .navbar-brand {
    color: #0d6efd;
    font-weight: 600;
}
.card {
    border-radius: 10px;
    background-color: #1e1e1e;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.7);
}
.btn-danger {
    background-color: #dc3545;
    border: none;
}
.btn-danger:hover {
    background-color: #a71d2a;
}
.alert {
    border-radius: 8px;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="#">Admin Panel</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="#"><i class="fas fa-users me-1"></i>Manage Users</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="manage_movies.php"><i class="fas fa-film me-1"></i>Manage Movies</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Page Content -->
<div class="container mt-5">
    <h2 class="text-center text-info mb-4"><i class="fas fa-users me-2"></i>Manage Users</h2>

    <div class="row mt-4">
        <?php foreach ($users as $user): ?>
            <div class="col-md-4 mb-3">
                <div class="card p-3">
                    <div class="card-body">
                        <h5 class="card-title text-info"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <p class="card-text">
                            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($user['contact']); ?>
                        </p>
                        <a href="?delete_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to delete this user?');">
                           <i class="fas fa-trash-alt me-1"></i> Delete User
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
