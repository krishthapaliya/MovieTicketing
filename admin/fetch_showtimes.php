<?php
include('../includes/db_connect.php');

$date = $_GET['date'];
$query = "SELECT * FROM showtimes WHERE date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$showtimes = [];
while ($row = $result->fetch_assoc()) {
    $showtimes[] = $row;
}

echo json_encode($showtimes);
?>
