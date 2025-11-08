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
$overallTotalSales = 0; // New variable for overall total sales

// Check if date range is submitted
if (isset($_POST['submit'])) {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $dateFilterLabel = "Sales from " . htmlspecialchars($startDate) . " to " . htmlspecialchars($endDate);

    // Fetch total sales by date in the selected range
    $salesQuery = "SELECT date, SUM(price) AS total_sales FROM bookings WHERE status = 'confirmed' AND date BETWEEN ? AND ? GROUP BY date ORDER BY date";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $salesResult = $stmt->get_result();
    $salesDataForChart = []; // Use a temporary array to calculate total

    while ($row = $salesResult->fetch_assoc()) {
        $dates[] = $row['date'];
        $totalSales[] = (float)$row['total_sales'];
        $salesDataForChart[] = (float)$row['total_sales'];
    }
    // Calculate total sales for the displayed range
    $overallTotalSales = array_sum($salesDataForChart);
    $stmt->close();

} else {
    $dateFilterLabel = "All-Time Sales Overview (Confirmed Bookings)";
    // Fetch total sales for the entire period by default
    $salesQuery = "SELECT date, SUM(price) AS total_sales FROM bookings WHERE status = 'confirmed' GROUP BY date ORDER BY date"; // Filter by 'confirmed' by default for sales
    $salesResult = $conn->query($salesQuery);
    $salesDataForChart = [];

    while ($row = $salesResult->fetch_assoc()) {
        $dates[] = $row['date'];
        $totalSales[] = (float)$row['total_sales'];
        $salesDataForChart[] = (float)$row['total_sales'];
    }
    // Calculate overall total sales
    $overallTotalSales = array_sum($salesDataForChart);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Modernized Dark Theme Styles */
        body {
            background-color: #212529; /* Dark background */
            color: #f8f9fa; /* Light text color */
            font-family: 'Arial', sans-serif;
        }
        .dashboard-card {
            background-color: #2c3034; /* Slightly lighter dark card background */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            border: 1px solid #495057; /* Subtle border */
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-title {
            font-weight: 500;
            color: #adb5bd; /* Muted light color for title */
        }
        .card-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: #f8f9fa;
        }
        /* Ensure icons are visible on the dark background */
        .dashboard-card i {
            color: #adb5bd !important;
        }
        .chart-container {
            background: #2c3034; /* Dark background for chart */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            margin: 30px 0;
            height: 50vh; 
            width: 100%;
            border: 1px solid #495057;
        }
        .date-inputs {
            margin: 20px 0;
            background: #2c3034; /* Dark background for form */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            border: 1px solid #495057;
        }
        /* Specific card color coding for better visual distinction on dark theme */
        .card-movies .card-text { color: #0dcaf0; } /* Light Cyan */
        .card-users .card-text { color: #198754; } /* Success Green */
        .card-bookings .card-text { color: #ffc107; } /* Warning Yellow */

        /* Input fields need contrast in dark mode */
        .form-control {
            background-color: #343a40;
            color: #f8f9fa;
            border-color: #495057;
        }
        .form-control:focus {
            background-color: #343a40;
            color: #f8f9fa;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        /* Ensure the labels are light */
        .form-label {
            color: #f8f9fa;
        }
        /* Horizontal rule visibility */
        hr {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

<?php include('../includes/admin_header.php'); ?>

<div class="container mt-5">
    <!-- Changed text-primary to text-info for better contrast in dark theme -->
    <h1 class="mb-5 text-center text-info">🎬 Admin Dashboard</h1>
    <hr>

    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card card-movies">
                <div class="card-body d-flex flex-column align-items-center">
                    <!-- Icon color is handled by CSS now -->
                    <i class="fas fa-film fa-3x mb-3 text-secondary"></i>
                    <h5 class="card-title">Total Movies</h5>
                    <p class="card-text"><?php echo $totalMovies; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card card-users">
                <div class="card-body d-flex flex-column align-items-center">
                    <i class="fas fa-users fa-3x mb-3 text-secondary"></i>
                    <h5 class="card-title">Total Users</h5>
                    <p class="card-text"><?php echo $totalUsers; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card card-bookings">
                <div class="card-body d-flex flex-column align-items-center">
                    <i class="fas fa-ticket-alt fa-3x mb-3 text-secondary"></i>
                    <h5 class="card-title">Total Bookings</h5>
                    <p class="card-text"><?php echo $totalBookings; ?></p>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <!-- Changed text-secondary to text-info for better contrast in dark theme -->
    <h2 class="text-center mb-4 text-info">💰 Sales Overview</h2>

    <div class="row justify-content-center mb-4">
        <div class="col-md-6">
            <!-- bg-primary text-white provides good contrast on the dark background -->
            <div class="card bg-primary text-white text-center dashboard-card">
                <div class="card-body">
                    <h5 class="card-title text-white">Total Sales (Confirmed)</h5>
                    <!-- Ensure text-white is used for card-text here -->
                    <p class="card-text text-white">Rs. <?php echo number_format($overallTotalSales, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="date-inputs">
        <form method="POST">
            <div class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required value="<?php echo isset($startDate) ? htmlspecialchars($startDate) : ''; ?>">
                </div>
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required value="<?php echo isset($endDate) ? htmlspecialchars($endDate) : ''; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="submit" class="btn btn-success w-100">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="chart-container">
        <h5 class="text-center mb-4 text-white"><?php echo $dateFilterLabel; ?></h5>
        <canvas id="salesChart"></canvas>
    </div>
</div>

<?php include('../includes/admin_footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Sales (Rs.)',
                data: <?php echo json_encode($totalSales); ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.4)', /* Success green/teal with transparency for fill */
                borderColor: '#198754', /* Success green/teal line */
                borderWidth: 3, 
                tension: 0.3, 
                pointBackgroundColor: '#198754',
                pointBorderColor: '#fff',
                pointBorderWidth: 1,
                pointRadius: 5,
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
                        font: { size: 14, weight: 'bold', color: '#f8f9fa' } /* Light text for dark theme */
                    },
                    ticks: {
                        color: '#adb5bd' /* Lighter ticks */
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)' /* Very light grid lines */
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date',
                        font: { size: 14, weight: 'bold', color: '#f8f9fa' } /* Light text for dark theme */
                    },
                    ticks: {
                        color: '#adb5bd' /* Lighter ticks */
                    },
                    grid: {
                        display: false 
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#f8f9fa' /* Light text for legend */
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 48, 52, 0.9)', /* Dark tooltip background */
                    titleColor: '#f8f9fa',
                    bodyColor: '#f8f9fa',
                    titleFont: { size: 14 },
                    bodyFont: { size: 14 },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rs.' + context.raw.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>