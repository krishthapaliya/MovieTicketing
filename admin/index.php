<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Include database connection
include('../includes/db_connect.php');

// Initialize variables for sales data
$salesData = [];
$dates = [];
$totalSales = [];

// Check if date range is submitted
if (isset($_POST['submit'])) {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    // Fetch total sales by date in the selected range
    $salesQuery = "SELECT date, SUM(price) AS total_sales FROM bookings WHERE status = 'confirmed' AND date BETWEEN ? AND ? GROUP BY date ORDER BY date";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $salesResult = $stmt->get_result();

    while ($row = $salesResult->fetch_assoc()) {
        $dates[] = $row['date'];
        $totalSales[] = (float)$row['total_sales'];
    }
} else {
    // Fetch total sales for the entire period by default
    $salesQuery = "SELECT date, SUM(price) AS total_sales FROM bookings GROUP BY date ORDER BY date";
    $salesResult = $conn->query($salesQuery);

    while ($row = $salesResult->fetch_assoc()) {
        $dates[] = $row['date'];
        $totalSales[] = (float)$row['total_sales'];
    }
}

// Fetch statistics from the database
$queryMovies = "SELECT COUNT(*) AS total_movies FROM movies";
$queryUsers = "SELECT COUNT(*) AS total_users FROM user_detail"; 
$queryBookings = "SELECT COUNT(*) AS total_bookings FROM bookings";

$totalMovies = $conn->query($queryMovies)->fetch_assoc()['total_movies'];
$totalUsers = $conn->query($queryUsers)->fetch_assoc()['total_users'];
$totalBookings = $conn->query($queryBookings)->fetch_assoc()['total_bookings'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f7f7f7;
        }
        .dashboard-card {
            margin: 20px 0;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 40vh;
            width: 80vw;
        }
        .date-inputs {
            margin: 20px 0;
        }
    </style>
</head>
<body>

<?php include('../includes/admin_header.php'); ?>

<div class="container">
    <h1 class="my-4">Admin Dashboard</h1>

   

    <div class="row">
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Total Movies</h5>
                    <p class="card-text"><?php echo $totalMovies; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="card-text"><?php echo $totalUsers; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Total Bookings</h5>
                    <p class="card-text"><?php echo $totalBookings; ?></p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="my-4 text-center">Sales Overview</h2>
     <!-- Date Range Form -->
     <form method="POST" class="date-inputs">
        <div class="row">
            <div class="col-md-5">
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-5">
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <div class="col-md-2">
                <button type="submit" name="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<?php include('../includes/admin_footer.php'); ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Sales (Confirmed Bookings)',
                data: <?php echo json_encode($totalSales); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales Amount (Rs.)',
                    },
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date',
                    },
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rs.' + context.raw.toFixed(2);
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>
