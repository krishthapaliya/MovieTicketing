<?php
include('classes/Database.php');
include('classes/Movie.php');
include('includes/header.php');

$db = new Database();
$conn = $db->conn;

// Search and sort values
$search_term = $_GET['search'] ?? "";
$sort_order = $_GET['sort'] ?? "date_desc";

// Payment message handling
$message = "";
if (isset($_GET['data'])) {
    $data = base64_decode($_GET['data']);
    $transaction_data = json_decode($data, true);

    if ($transaction_data) {
        $transaction_uuid = $transaction_data['transaction_uuid']; 
        $status = $transaction_data['status']; 
        if ($status == 'COMPLETE') {
            $stmt = $conn->prepare("UPDATE bookings SET status='Completed' WHERE transaction_uuid=?");
            $stmt->bind_param('s', $transaction_uuid);
            $message = $stmt->execute() ? 
                "Payment successful! Your booking has been confirmed." : 
                "Payment was successful, but we couldn't update your booking status.";
            $stmt->close();
        } else {
            $message = "Payment failed or is pending! Please contact support.";
        }
    } else {
        $message = "Invalid payment data received.";
    }
}

// Fetch movies using OOP
$movieObj = new Movie($conn);
$movies = $movieObj->getMovies($search_term, $sort_order);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MovieMania - Now Showing</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #121212; 
    color: #e0e0e0;
    font-family: 'Poppins', sans-serif;
}
.card-movie {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    background-color: #1e1e1e; 
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
}
.card-movie:hover {
    transform: translateY(-10px);
    box-shadow: 0 16px 28px rgba(0, 0, 0, 0.6);
}
.card-movie img {
    height: 380px; 
    object-fit: cover;
    box-shadow: 0 4px 10px rgba(0,0,0,0.5); 
}
.card-body { padding: 1.5rem; }
.card-body h5 { font-weight: 800; color: #ffffff; margin-bottom: 0.5rem; }
.card-body p { font-size: 0.9rem; color: #a0a0a0; }
.btn-primary { background-color: #e50914; border: none; font-weight: 600; transition: 0.3s; }
.btn-primary:hover { background-color: #ff2a36; transform: translateY(-2px); }
.btn-primary:active { background-color: #b3070f; transform: translateY(0); }
.section-title { font-size: 2.5rem; font-weight: 900; color: #ffffff; letter-spacing: 1px; padding-bottom: 10px; border-bottom: 3px solid #e50914; display: inline-block; margin-bottom: 2rem !important; }
.alert-message { margin-top: 20px; color: #0f5132; background-color: #d1e7dd; border-color: #badbcc; border-radius: 8px; font-weight: 600; }
.search-form { background-color: #1e1e1e; border-radius: 8px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
.search-form .form-control, .search-form .form-select { background-color: #2a2a2a; border: 1px solid #3e3e3e; color: #e0e0e0; }
.search-form .form-control::placeholder { color: #a0a0a0; }
.search-form .form-select option { background-color: #2a2a2a; color: #e0e0e0; }
</style>
</head>
<body>

<div class="container mt-5 mb-5">

<?php 
$alert_class = '';
if(!empty($message)) {
    if (strpos($message, 'successful') !== false) $alert_class = 'alert-success';
    else if (strpos($message, 'failed') !== false || strpos($message, "couldn't update") !== false) $alert_class = 'alert-warning';
    else $alert_class = 'alert-info';
?>
    <div class="alert <?php echo $alert_class; ?> text-center alert-message" role="alert">
        <?php echo $message; ?>
    </div>
<?php } ?>

<div class="text-center mb-5">
    <h2 class="section-title">🎬 Now Showing</h2>
    <p class="lead text-muted">Experience the magic of cinema. Book your tickets now!</p>
</div>

<div class="row mb-5 justify-content-center">
    <div class="col-lg-10">
        <form action="index.php" method="GET" class="search-form">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div class="input-group">
                        <input type="search" name="search" class="form-control form-control-lg" placeholder="Search for a movie name or keyword..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                        <?php if (!empty($search_term)): ?>
                        <a href="index.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-white border-dark"><i class="fas fa-sort-amount-down"></i> Sort By:</span>
                        <select name="sort" onchange="this.form.submit()" class="form-select form-select-lg">
                            <option value="date_desc" <?php if ($sort_order == 'date_desc') echo 'selected'; ?>>Newest First (Date)</option>
                            <option value="date_asc" <?php if ($sort_order == 'date_asc') echo 'selected'; ?>>Oldest First (Date)</option>
                            <option value="a_to_z" <?php if ($sort_order == 'a_to_z') echo 'selected'; ?>>A to Z (Title)</option>
                            <option value="z_to_a" <?php if ($sort_order == 'z_to_a') echo 'selected'; ?>>Z to A (Title)</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-5">
<?php
if (!empty($movies)) {
    if (!empty($search_term)) {
        echo "<div class='col-12'><h3 class='text-light'>Showing **" . count($movies) . "** results for: *\"" . htmlspecialchars($search_term) . "\"*</h3></div>";
    }
    foreach ($movies as $movie) {
?>
<div class="col-lg-4 col-md-6 col-sm-12">
    <div class="card card-movie">
        <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="text-decoration-none">
            <img src="uploaded_img/<?php echo rawurlencode($movie['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['name']); ?> Poster">
        </a>
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($movie['name']); ?></h5>
            <p class="card-text text-truncate-3"><?php echo htmlspecialchars(substr($movie['details'], 0, 100)) . '...'; ?></p>
            <p class="card-text"><small class="text-info">⭐ **Release:**</small> <span class="text-light"><?php echo htmlspecialchars($movie['release_date']); ?></span></p>
            <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary w-100 mt-2">Book Tickets</a>
        </div>
    </div>
</div>
<?php
    }
} else {
    $no_movie_message = !empty($search_term) 
        ? "Sorry, no movies match your search for \"**" . htmlspecialchars($search_term) . "**\"." 
        : "No movies available at the moment.";
    echo "<div class='col-12'><p class='text-center fs-4 text-light'>" . $no_movie_message . "</p></div>";
}
?>
</div>
</div>

<?php include('includes/footer.php'); ?>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
