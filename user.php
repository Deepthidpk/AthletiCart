<?php

require_once "connect.php";
class User {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function register($name, $phone_no, $email, $password) {
        // Check if email already exists in tbl_login
        $checkEmail = $this->conn->prepare("SELECT * FROM tbl_login WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Email already registered!');</script>";
            return false;
        }

        // Step 1: Insert into tbl_user
        $stmt_user = $this->conn->prepare("INSERT INTO tbl_user (name, phone_no) VALUES (?, ?)");
        $stmt_user->bind_param("ss", $name, $phone_no);
        $stmt_user->execute();

        // Get the inserted user_id
        $user_id = $stmt_user->insert_id;

        // Step 2: Insert into tbl_login
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $role = "user";
        $status = "active";

        $stmt_login = $this->conn->prepare("INSERT INTO tbl_login (user_id, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt_login->bind_param("issss", $user_id, $email, $hashedPassword, $role, $status);

        if ($stmt_login->execute()) {
            return true;
        } else {
            echo "<script>alert('Error during registration!');</script>";
            return false;
        }
    }
}
?>
