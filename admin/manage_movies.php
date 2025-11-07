<?php
// Include database connection and header
include('../includes/db_connect.php');
include('../includes/admin_header.php');

// Initialize variables for movie data
$id = '';
$name = '';
$details = '';
$image = '';
$release_date = '';

// Handle form submission for adding or editing movies
if (isset($_POST['save_movie'])) {
    $name = trim($_POST['name']);
    $details = trim($_POST['details']);
    $release_date = $_POST['release_date'];
    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_folder = '../uploaded_img/' . $image_name;

    // Validate input data
    $errors = [];
    if (empty($name)) {
        $errors[] = "Movie name is required.";
    }

    if (empty($details) || preg_match('/^\d+$/', $details) || str_word_count($details) < 2) {
        $errors[] = "Movie details must be at least two words and not just numbers.";
    }

    if (empty($release_date)) {
        $errors[] = "Release date is required.";
    }

    if (empty($image_name) && empty($_POST['old_image'])) {
        $errors[] = "Movie image is required.";
    }

    if (empty($errors)) {
        // Handle image upload if new image is provided
        if (!empty($image_name) && move_uploaded_file($image_tmp, $image_folder)) {
            // Update movie
            if (isset($_POST['id']) && $_POST['id'] != '') {
                $id = $_POST['id'];
                $query = "UPDATE movies SET name = ?, details = ?, image = ?, release_date = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssi", $name, $details, $image_name, $release_date, $id);
                $stmt->execute();
                $message = "Movie updated successfully!";
            } else {
                // Insert new movie
                $query = "INSERT INTO movies (name, details, image, release_date) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $name, $details, $image_name, $release_date);
                $stmt->execute();
                $message = "Movie added successfully!";
            }
        } else {
            // Use old image if no new image is uploaded
            $old_image = $_POST['old_image'];
            if (isset($_POST['id']) && $_POST['id'] != '') {
                $id = $_POST['id'];
                $query = "UPDATE movies SET name = ?, details = ?, image = ?, release_date = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssi", $name, $details, $old_image, $release_date, $id);
                $stmt->execute();
                $message = "Movie updated successfully!";
            } else {
                $query = "INSERT INTO movies (name, details, image, release_date) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $name, $details, $old_image, $release_date);
                $stmt->execute();
                $message = "Movie added successfully!";
            }
        }
    }
}

// Handle delete movie
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM movies WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = "Movie deleted successfully!";
}

// Fetch movies from the database
$query = "SELECT * FROM movies";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .container {
            max-width: 900px;
            margin-top: 50px;
        }

        .card {
            padding: 10px;
            align-items: center;
        }

        .card-img-top {
            width: 70%;
            object-fit: cover;
        }

        .alert-dismissible .btn-close {
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Manage Movies</h2>
        <?php if (isset($message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#movieModal">Add
            Movie</button>

        <!-- Movie List -->
        <div class="row">
            <?php while ($movie = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <img src="../uploaded_img/<?php echo $movie['image']; ?>" class="card-img-top" alt="Movie Image"
                            style="height: 300px;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $movie['name']; ?></h5>
                            <p class="card-text"><?php echo $movie['details']; ?></p>
                            <p><strong>Release Date:</strong> <?php echo $movie['release_date']; ?></p>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#movieModal"
                                data-id="<?php echo $movie['id']; ?>" data-name="<?php echo $movie['name']; ?>"
                                data-details="<?php echo $movie['details']; ?>" data-image="<?php echo $movie['image']; ?>"
                                data-release-date="<?php echo $movie['release_date']; ?>">Edit</button>
                            <a href="manage_movies.php?delete=<?php echo $movie['id']; ?>" class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to delete this movie?');">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Movie Modal -->
    <div class="modal fade" id="movieModal" tabindex="-1" aria-labelledby="movieModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="movieModalLabel">Add/Edit Movie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="movieForm" action="manage_movies.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="movieId" value="">
                        <input type="hidden" name="old_image" id="oldImage" value="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Movie Name</label>
                            <input type="text" name="name" id="movieName" class="form-control" required>
                            <div id="name-error" class="error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="details" class="form-label">Movie Details</label>
                            <textarea name="details" id="movieDetails" class="form-control" required></textarea>
                            <div id="details-error" class="error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Movie Image (Image files only)</label>
                            <input type="file" name="image" id="movieImage" class="form-control" accept="image/*">
                            <div id="image-error" class="error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="release_date" class="form-label">Release Date</label>
                            <input type="date" name="release_date" id="releaseDate" class="form-control" required>
                            <div id="release-date-error" class="error"></div>
                        </div>
                        <button type="submit" name="save_movie" class="btn btn-primary">Save Movie</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Include footer
    include('../includes/admin_footer.php');
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const movieModal = document.getElementById('movieModal');
        const movieForm = document.getElementById('movieForm');
        const nameInput = document.getElementById('movieName');
        const detailsInput = document.getElementById('movieDetails');
        const imageInput = document.getElementById('movieImage');
        const releaseDateInput = document.getElementById('releaseDate');
        const submitButton = document.getElementById('submit-button');

        const nameError = document.getElementById('name-error');
        const detailsError = document.getElementById('details-error');
        const imageError = document.getElementById('image-error');
        const releaseDateError = document.getElementById('release-date-error');

        // Function to validate movie name
        function validateName() {
            const name = nameInput.value.trim();
            // Regular expression to allow only letters and spaces
            const namePattern = /^[A-Za-z0-9\s]+$/;

            if (name === '') {
                nameError.textContent = "Movie name is required.";
                return false;
            } else if (!namePattern.test(name)) {
                nameError.textContent = "Movie name can only contain letters and spaces.";
                return false;
            } else {
                nameError.textContent = "";
                return true;
            }
        }


        // Function to validate movie details
        function validateDetails() {
            const details = detailsInput.value.trim();
            if (details === '' || details.split(' ').length < 2 || /^\d+$/.test(details)) {
                detailsError.textContent = "Movie details must be at least two words and not just numbers.";
                return false;
            } else {
                detailsError.textContent = "";
                return true;
            }
        }

        // Function to validate movie image
        function validateImage() {
            const image = imageInput.files[0];
            const oldImage = document.getElementById('oldImage').value;
            if (!image && !oldImage) {
                imageError.textContent = "Movie image is required.";
                return false;
            } else if (image && !image.type.startsWith('image/')) {
                imageError.textContent = "Please upload a valid image file.";
                return false;
            } else {
                imageError.textContent = "";
                return true;
            }
        }

        // Function to validate release date
        function validateReleaseDate() {
            const releaseDate = releaseDateInput.value;
            if (releaseDate === '') {
                releaseDateError.textContent = "Release date is required.";
                return false;
            } else {
                releaseDateError.textContent = "";
                return true;
            }
        }

        // Function to validate the entire form
        function validateForm() {
            const isValidName = validateName();
            const isValidDetails = validateDetails();
            const isValidImage = validateImage();
            const isValidReleaseDate = validateReleaseDate();

            return isValidName && isValidDetails && isValidImage && isValidReleaseDate;
        }

        // Real-time validation on input fields
        nameInput.addEventListener('input', validateName);
        detailsInput.addEventListener('input', validateDetails);
        imageInput.addEventListener('change', validateImage);
        releaseDateInput.addEventListener('change', validateReleaseDate);

        // Validate form on submission
        movieForm.addEventListener('submit', function (e) {
            if (!validateForm()) {
                e.preventDefault(); // Prevent form submission if validation fails
            }
        });

        // Set modal data when editing a movie
        movieModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const details = button.getAttribute('data-details');
            const image = button.getAttribute('data-image');
            const releaseDate = button.getAttribute('data-release-date');

            const modalTitle = movieModal.querySelector('.modal-title');
            const movieIdInput = movieModal.querySelector('#movieId');
            const movieNameInput = movieModal.querySelector('#movieName');
            const movieDetailsInput = movieModal.querySelector('#movieDetails');
            const movieImageInput = movieModal.querySelector('#movieImage');
            const oldImageInput = movieModal.querySelector('#oldImage');
            const releaseDateInput = movieModal.querySelector('#releaseDate');

            if (id) {
                modalTitle.textContent = 'Edit Movie';
                movieIdInput.value = id;
                movieNameInput.value = name;
                movieDetailsInput.value = details;
                oldImageInput.value = image;
                releaseDateInput.value = releaseDate;
            } else {
                modalTitle.textContent = 'Add Movie';
                movieIdInput.value = '';
                movieNameInput.value = '';
                movieDetailsInput.value = '';
                oldImageInput.value = '';
                releaseDateInput.value = '';
                movieImageInput.value = '';
            }
        });
    </script>

</body>

</html>