// classes/Booking.php
<?php
class Booking {
    private $conn;

    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    public function createBooking($user_id, $movie_id, $showtime_id, $booked_seats, $total_price) {
        $status = 'Pending';
        $transaction_uuid = bin2hex(random_bytes(16));
        $date = date('Y-m-d');

        $stmt = $this->conn->prepare(
            "INSERT INTO bookings (user_id, date, showtime, price, status, booked_seats, transaction_uuid) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isidsss", $user_id, $date, $showtime_id, $total_price, $status, $booked_seats, $transaction_uuid);
        if($stmt->execute()){
            return $transaction_uuid;
        }
        return false;
    }

    public function getBookedSeats($showtime_id) {
        $stmt = $this->conn->prepare("SELECT booked_seats FROM bookings WHERE showtime = ? AND status='Completed'");
        $stmt->bind_param("i", $showtime_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $seats = [];
        while($row = $result->fetch_assoc()){
            if(!empty($row['booked_seats'])){
                $seats = array_merge($seats, explode(',', $row['booked_seats']));
            }
        }
        return $seats;
    }
}
?>
