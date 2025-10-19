<?php
session_start();
require_once "connect.php";

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ✅ Ensure $conn is active
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli("localhost", "root", "", "athleticart");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
}

/* ===========================
   CLASS DEFINITIONS
=========================== */
class UserManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Deactivate user
    public function deactivateUser($userId) {
        $stmt = $this->conn->prepare("UPDATE tbl_login SET status='inactive' WHERE login_id=?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    // Activate user
    public function activateUser($userId) {
        $stmt = $this->conn->prepare("UPDATE tbl_login SET status='active' WHERE login_id=?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    // Get user status
    public function getUserStatus($userId) {
        $stmt = $this->conn->prepare("SELECT status FROM tbl_login WHERE login_id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['status'] ?? null;
    }
}

/* ===========================
   MAIN FUNCTIONALITY
=========================== */
$userObj = new UserManager($conn);

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int)$_GET['id'];

    $currentStatus = $userObj->getUserStatus($userId);

    if ($currentStatus === 'active') {
        $userObj->deactivateUser($userId);
        $_SESSION['msg'] = "User has been deactivated successfully.";
    } elseif ($currentStatus === 'inactive') {
        $userObj->activateUser($userId);
        $_SESSION['msg'] = "User has been activated successfully.";
    } else {
        $_SESSION['msg'] = "User not found.";
    }
}

header("Location: manage_users.php");
exit();
?>
