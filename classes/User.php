<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/Validation.php';

class User {
    private $conn;

    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    private function customHash($password) {
        $salt = 'vfshbhjv@#2343'; 
        $hashed = '';
        for ($i = 0; $i < strlen($password); $i++) {
            $hashed .= dechex(ord($password[$i]) + ord($salt[$i % strlen($salt)]));
        }
        return $hashed;
    }

    public function usernameExists($username) {
        $query = "SELECT id FROM user_detail WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function register($username, $email, $contact, $password, $confirmPassword) {
        $errors = [];

        // Validation checks
        if (empty($username) || empty($email) || empty($contact) || empty($password) || empty($confirmPassword)) {
            $errors[] = "All fields are required.";
        }

        if (!Validation::validateUsername($username)) {
            $errors[] = "Username cannot start with a number or contain special symbols.";
        }

        if (!Validation::validateEmail($email)) {
            $errors[] = "Invalid email address.";
        }

        if (!Validation::validateContact($contact)) {
            $errors[] = "Contact must be a valid 10-digit number.";
        }

        if (!Validation::validatePassword($password)) {
            $errors[] = "Password must contain uppercase, lowercase, number, and symbol.";
        }

        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }

        if ($this->usernameExists($username)) {
            $errors[] = "Username is already taken.";
        }

        // If no errors, insert into DB
        if (empty($errors)) {
            $hashedPassword = $this->customHash($password);
            $query = "INSERT INTO user_detail (username, email, contact, password) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssss", $username, $email, $contact, $hashedPassword);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errors[] = "Database error: " . $this->conn->error;
            }
        }

        return ['success' => false, 'errors' => $errors];
    }
}
?>
