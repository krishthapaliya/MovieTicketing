<?php
include('includes/db_connect.php'); 
include('includes/header.php'); 

// --- 🎬 START: New Search & Sort Logic ---

$search_term = "";
$search_condition = "";
$sort_order = "date_desc"; // Default sort order
$order_by_sql = "release_date DESC"; // Default SQL ORDER BY

// 1. Check if a search query was submitted
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    
    // 2. Prepare the search condition for the SQL query
    $search_param = "%" . $search_term . "%";
    
    // 3. Define the WHERE clause using a prepared statement placeholder
    $search_condition = " WHERE name LIKE ? OR details LIKE ?";
}

// 4. Check if a sort order was submitted
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    $sort_order = $_GET['sort'];

    // 5. Define the SQL ORDER BY clause based on the chosen sort order
    switch ($sort_order) {
        case 'a_to_z':
            $order_by_sql = "name ASC";
            break;
        case 'z_to_a':
            $order_by_sql = "name DESC";
            break;
        case 'date_asc':
            $order_by_sql = "release_date ASC";
            break;
        case 'date_desc': // This is the default case, but we keep it explicit
        default:
            $order_by_sql = "release_date DESC";
            break;
    }
}

// --- 🎬 END: New Search & Sort Logic ---

// Payment message handling (existing code - unchanged for brevity)
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MovieMania - Now Showing</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* ... (Your existing CSS styles remain here) ... */
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
.card-body {
    padding: 1.5rem;
}
.card-body h5 {
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0.5rem;
}
.card-body p {
    font-size: 0.9rem;
    color: #a0a0a0;
}
.btn-primary {
    background-color: #e50914;
    border: none;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.1s;
}
.btn-primary:hover {
    background-color: #ff2a36;
    transform: translateY(-2px);
}
.btn-primary:active {
    background-color: #b3070f;
    transform: translateY(0);
}
.section-title {
    font-size: 2.5rem;
    font-weight: 900;
    color: #ffffff;
    letter-spacing: 1px;
    padding-bottom: 10px;
    border-bottom: 3px solid #e50914;
    display: inline-block;
    margin-bottom: 2rem !important;
}
.alert-message {
    margin-top: 20px;
    color: #0f5132;
    background-color: #d1e7dd;
    border-color: #badbcc;
    border-radius: 8px;
    font-weight: 600;
}
/* New style for search form */
.search-form {
    background-color: #1e1e1e;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}
.search-form .form-control, .search-form .form-select { /* Added form-select */
    background-color: #2a2a2a;
    border: 1px solid #3e3e3e;
    color: #e0e0e0;
}
.search-form .form-control::placeholder {
    color: #a0a0a0;
}
.search-form .form-select option {
    background-color: #2a2a2a; /* Ensure option background is dark */
    color: #e0e0e0;
}
</style>
</head>
<body>

<div class="container mt-5 mb-5">
    
    <?php 
    $alert_class = '';
    if(!empty($message)) {
        if (strpos($message, 'successful') !== false) {
            $alert_class = 'alert-success';
        } else if (strpos($message, 'failed') !== false || strpos($message, 'couldn\'t update') !== false) {
            $alert_class = 'alert-warning';
        } else {
            $alert_class = 'alert-info';
        }
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
        <div class="col-lg-10"> <form action="index.php" method="GET" class="search-form">
                <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                        <div class="input-group">
                            <input 
                                type="search" 
                                name="search" 
                                class="form-control form-control-lg" 
                                placeholder="Search for a movie name or keyword..."
                                value="<?php echo htmlspecialchars($search_term); ?>"
                            >
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if (!empty($search_term)): ?>
                                <a href="index.php" class="btn btn-secondary">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white border-dark">
                                <i class="fas fa-sort-amount-down"></i> Sort By:
                            </span>
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
        // --- 🎬 Revised Movie Fetching with Prepared Statement ---

        // 1. Construct the full query, using the dynamically generated ORDER BY clause
        $query = "SELECT * FROM movies" . $search_condition . " ORDER BY " . $order_by_sql;
        
        // 2. Prepare the statement
        $stmt_movies = $conn->prepare($query);

        if ($search_condition) {
            // 3. Bind parameters if a search term exists (binding twice for both LIKE clauses)
            $stmt_movies->bind_param('ss', $search_param, $search_param);
        }

        // 4. Execute the statement
        $stmt_movies->execute();
        
        // 5. Get the result
        $result = $stmt_movies->get_result();

        if ($result->num_rows > 0) {
            // Display results header based on search
            if (!empty($search_term)) {
                echo "<div class='col-12'><h3 class='text-light'>Showing **" . $result->num_rows . "** results for: *\"" . htmlspecialchars($search_term) . "\"*</h3></div>";
            }
            
            while ($movie = $result->fetch_assoc()) {
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card card-movie">
                <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="text-decoration-none">
                    <img src="uploaded_img/<?php echo urlencode($movie['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['name']); ?> Poster">
                </a>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($movie['name']); ?></h5>
                    <p class="card-text text-truncate-3"><?php echo htmlspecialchars(substr($movie['details'], 0, 100)) . '...'; ?></p>
                    <p class="card-text">
                        <small class="text-info">⭐ **Release:**</small> 
                        <span class="text-light"><?php echo htmlspecialchars($movie['release_date']); ?></span>
                    </p>
                    <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary w-100 mt-2">Book Tickets</a>
                </div>
            </div>
        </div>
        <?php
            }
        } else {
            // Display message if no results found
            $no_movie_message = !empty($search_term) 
                ? "Sorry, no movies match your search for \"**" . htmlspecialchars($search_term) . "**\"." 
                : "No movies available at the moment.";
            echo "<div class='col-12'><p class='text-center fs-4 text-light'>" . $no_movie_message . "</p></div>";
        }
        
        // 6. Close the statement
        $stmt_movies->close();
        ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>