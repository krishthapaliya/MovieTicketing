<?php
class Validation {
    public static function validateUsername($username) {
        return preg_match("/^[A-Za-z][A-Za-z0-9]*$/", $username);
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validateContact($contact) {
        return preg_match("/^[0-9]{10}$/", $contact);
    }

    public static function validatePassword($password) {
        return strlen($password) >= 8 &&
               preg_match("/[A-Z]/", $password) &&
               preg_match("/[a-z]/", $password) &&
               preg_match("/[0-9]/", $password) &&
               preg_match("/[\W]/", $password);
    }
}
?>
