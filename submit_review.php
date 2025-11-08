<?php
session_start();
include('includes/db_connect.php');

// Ensure the response is always JSON
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$movie_id = $_POST['movie_id'] ?? null;
$rating = $_POST['rating'] ?? null;



// --- 2. Input Validation ---
// Ensure movie_id is numeric and rating is between 1 and 5
if (!is_numeric($movie_id) || !is_numeric($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating data submitted.']);
    exit;
}

// --- 3. Check for Existing Rating ---
$stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id=? AND movie_id=?");
$stmt->bind_param("ii", $user_id, $movie_id);
if (!$stmt->execute()) {
    // Handle database execution error
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Database error during check.']);
    exit;
}
$res = $stmt->get_result();
$stmt->close(); // Close statement after use

// --- 4. Insert or Update Rating ---
if($res->num_rows > 0){
    // Update rating (Use 'i' for $rating, since it was validated as numeric)
    $update_stmt = $conn->prepare("UPDATE reviews SET rating=?, created_at=NOW() WHERE user_id=? AND movie_id=?");
    $update_stmt->bind_param("iii", $rating, $user_id, $movie_id);
    
    if (!$update_stmt->execute()) {
        $update_stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update rating.']);
        exit;
    }
    $update_stmt->close();

} else {
    // Insert rating
    $insert_stmt = $conn->prepare("INSERT INTO reviews (movie_id, user_id, rating) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iii", $movie_id, $user_id, $rating);
    
    if (!$insert_stmt->execute()) {
        $insert_stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to insert new rating.']);
        exit;
    }
    $insert_stmt->close();
}

// --- 5. Get New Average Rating ---
$avg_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM reviews WHERE movie_id=?");
$avg_stmt->bind_param("i", $movie_id);

if (!$avg_stmt->execute()) {
    $avg_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve new average rating.']);
    exit;
}

$result = $avg_stmt->get_result()->fetch_assoc();
$avg_stmt->close();

// --- 6. Success Response ---
echo json_encode([
    'success' => true,
    'avg_rating' => round($result['avg_rating'], 1),
    'total_ratings' => $result['total_ratings']
]);
?>