<?php
session_start();

class Logout {
    public function __construct() {
        // If user is logged in, destroy session
        if (isset($_SESSION['login_id'])) {
            $this->logoutUser();
        } else {
            // Redirect to login if no session
            header("Location: login.php");
            exit();
        }
    }

    private function logoutUser() {
        // Unset all session variables
        $_SESSION = [];

        // Destroy the session
        session_destroy();

        // Redirect to login page
        header("Location: login.php");
        exit();
    }
}

// Create an object and execute logout
new Logout();
?>
