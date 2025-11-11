<?php
class Movie {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function getMovies($search_term = "", $sort_order = "date_desc") {
        $search_condition = "";
        $order_by_sql = "release_date DESC";

        // Sorting
        switch ($sort_order) {
            case 'a_to_z': $order_by_sql = "name ASC"; break;
            case 'z_to_a': $order_by_sql = "name DESC"; break;
            case 'date_asc': $order_by_sql = "release_date ASC"; break;
            case 'date_desc':
            default: $order_by_sql = "release_date DESC"; break;
        }

        if (!empty(trim($search_term))) {
            $search_condition = " WHERE name LIKE ? OR details LIKE ?";
            $search_param = "%" . trim($search_term) . "%";

            $stmt = $this->conn->prepare("SELECT * FROM movies" . $search_condition . " ORDER BY " . $order_by_sql);
            $stmt->bind_param("ss", $search_param, $search_param);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM movies ORDER BY " . $order_by_sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $movies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $movies;
    }
}
?>
