<?php
include('../includes/db_connect.php');
include('../includes/admin_header.php');

$id = '';
$name = '';
$details = '';
$image = '';
$release_date = '';
$errors = [];

if (isset($_POST['save_movie'])) {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $name = trim($_POST['name']);
    $details = trim($_POST['details']);
    $release_date = $_POST['release_date'];
    $old_image = $_POST['old_image'];
    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_folder = '../uploaded_img/' . $image_name;
    $current_image = !empty($image_name) ? $image_name : $old_image;

    if (empty($name)) $errors[] = "Movie name is required.";
    if (empty($details) || preg_match('/^\d+$/', $details) || str_word_count($details) < 2) $errors[] = "Movie details must be at least two words and not just numbers.";
    if (empty($release_date)) $errors[] = "Release date is required.";
    if (empty($image_name) && empty($old_image)) $errors[] = "Movie image is required for a new entry.";

    if (empty($errors)) {
        if (!empty($image_name)) {
            if (!move_uploaded_file($image_tmp, $image_folder)) $errors[] = "Failed to upload new image.";
        }
        if (empty($errors)) {
            if (!empty($id)) {
                $query = "UPDATE movies SET name = ?, details = ?, image = ?, release_date = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssi", $name, $details, $current_image, $release_date, $id);
                $stmt->execute();
                $message = "Movie updated successfully!";
            } else {
                $query = "INSERT INTO movies (name, details, image, release_date) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $name, $details, $current_image, $release_date);
                $stmt->execute();
                $message = "Movie added successfully!";
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM movies WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = "Movie deleted successfully!";
}

$query = "SELECT * FROM movies ORDER BY release_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Movies - Dark Theme</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
body {
    background-color: #121212;
    color: #e0e0e0;
}
.container {
    max-width: 1200px;
    margin-top: 30px;
}
.alert {
    border-radius: 8px;
}
.alert-success {
    background-color: #1e4620;
    color: #d4edda;
}
.alert-danger {
    background-color: #4a1c1c;
    color: #f8d7da;
}
.alert-warning {
    background-color: #4e3c1c;
    color: #fff3cd;
}

.movie-card {
    border: none;
    border-radius: 12px;
    background-color: #1f1f1f;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}
.movie-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.7);
}
.card-img-container {
    height: 350px;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #2a2a2a;
}
.card-img-top {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.movie-card:hover .card-img-top {
    transform: scale(1.05);
}
.card-body {
    padding: 15px;
}
.card-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0d6efd;
    margin-bottom: 8px;
}
.card-text-details {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 15px;
    color: #b0b0b0;
}
.btn-primary {
    background-color: #0d6efd;
    border: none;
}
.btn-primary:hover {
    background-color: #084298;
}
.btn-warning {
    background-color: #ffc107;
    color: #000;
}
.btn-danger {
    background-color: #dc3545;
}
.modal-content {
    background-color: #1e1e1e;
    color: #e0e0e0;
    border-radius: 12px;
}
.modal-header {
    border-bottom: 1px solid #444;
}
.modal-footer {
    border-top: 1px solid #444;
}
.form-control {
    background-color: #2a2a2a;
    border: 1px solid #444;
    color: #e0e0e0;
}
.form-control:focus {
    background-color: #2a2a2a;
    color: #e0e0e0;
    border-color: #0d6efd;
    box-shadow: none;
}
.error {
    color: #ff6b6b;
    font-size: 0.85em;
}
#currentImagePreview {
    display: none;
    margin-top: 10px;
    border: 1px solid #444;
    padding: 5px;
    border-radius: 5px;
    background-color: #2a2a2a;
}
</style>
</head>

<body>
<div class="container">
    <h1 class="my-4 text-center text-info"><i class="fas fa-film me-2"></i>Manage Movies</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Please correct the following errors:
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#movieModal">
            <i class="fas fa-plus me-2"></i> Add New Movie
        </button>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($movie = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card movie-card h-100">
                        <div class="card-img-container">
                            <img src="../uploaded_img/<?php echo htmlspecialchars($movie['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['name']); ?> Poster">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($movie['name']); ?></h5>
                            <p class="card-text card-text-details flex-grow-1"><?php echo htmlspecialchars($movie['details']); ?></p>
                            <p class="card-text mb-3"><small class="text">
                                <i class="far fa-calendar-alt me-1"></i> <strong>Release:</strong> <?php echo htmlspecialchars($movie['release_date']); ?>
                            </small></p>
                            <div class="mt-auto d-flex justify-content-between">
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#movieModal"
                                    data-id="<?php echo $movie['id']; ?>" data-name="<?php echo htmlspecialchars($movie['name']); ?>"
                                    data-details="<?php echo htmlspecialchars($movie['details']); ?>" data-image="<?php echo htmlspecialchars($movie['image']); ?>"
                                    data-release-date="<?php echo htmlspecialchars($movie['release_date']); ?>">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <a href="manage_movies.php?delete=<?php echo $movie['id']; ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete the movie: <?php echo htmlspecialchars($movie['name']); ?>?');">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i> No movies found in the database. Add a new one!
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="movieModal" tabindex="-1" aria-labelledby="movieModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-info">
                <h5 class="modal-title" id="movieModalLabel"><i class="fas fa-plus-circle me-2"></i> Add/Edit Movie</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="movieForm" action="manage_movies.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="movieId">
                    <input type="hidden" name="old_image" id="oldImage">

                    <div class="mb-3">
                        <label for="movieName" class="form-label">Movie Name</label>
                        <input type="text" name="name" id="movieName" class="form-control" required>
                        <div id="name-error" class="error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="movieDetails" class="form-label">Movie Details</label>
                        <textarea name="details" id="movieDetails" class="form-control" rows="4" required></textarea>
                        <div id="details-error" class="error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="movieImage" class="form-label">Movie Image (Image files only)</label>
                        <input type="file" name="image" id="movieImage" class="form-control" accept="image/*">
                        <div id="image-error" class="error"></div>
                        <div id="currentImagePreview" class="d-flex align-items-center">
                            <small class="me-2 text-muted">Current Image:</small>
                            <span id="currentImageName"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="releaseDate" class="form-label">Release Date</label>
                        <input type="date" name="release_date" id="releaseDate" class="form-control" required>
                        <div id="release-date-error" class="error"></div>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="save_movie" id="submit-button" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Movie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/admin_footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
// JavaScript validation & modal logic remains the same as in your code
// You can keep your current JS for real-time validation and modal setup
</script>

</body>
</html>
