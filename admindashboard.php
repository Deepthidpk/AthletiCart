<?php
session_start();
require_once "connect.php"; // your DB connection file

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Ensure $conn exists
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli("localhost", "root", "", "athleticart");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
}

/* ===========================
   DATABASE CLASSES
=========================== */
class UserManager {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function getTotalUsers() {
        $res = $this->conn->query("SELECT COUNT(*) AS total FROM tbl_login WHERE role='user'");
        return $res->fetch_assoc()['total'];
    }
}

class ProductManager {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function getTotalProducts() {
        $res = $this->conn->query("SELECT COUNT(*) AS total FROM tbl_products");
        return $res->fetch_assoc()['total'];
    }
}

/* ===========================
   OBJECT CREATION
=========================== */
$userObj = new UserManager($conn);
$productObj = new ProductManager($conn);

/* ===========================
   DATA FETCHING
=========================== */
$totalUsers = $userObj->getTotalUsers();
$totalProducts = $productObj->getTotalProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    display: flex;
    background-color: #f5f7fa;
}
.sidebar {
    width: 250px;
    background-color: #2b2b52;
    color: #fff;
    height: 100vh;
    padding: 20px;
    position: fixed;
}
.sidebar h3 {
    text-align: center;
    color: #00ccff;
    margin-bottom: 20px;
}
.sidebar .profile {
    text-align: center;
    margin-bottom: 20px;
}
.sidebar .profile img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
}
.sidebar .profile p {
    margin-top: 10px;
}
.sidebar a {
    display: block;
    color: #fff;
    text-decoration: none;
    padding: 10px 0;
    margin: 10px 0;
}
.sidebar a:hover {
    background-color: #00ccff;
    border-radius: 5px;
    padding-left: 10px;
}
.main {
    margin-left: 270px;
    padding: 20px;
    width: calc(100% - 270px);
}
.cards {
    display: flex;
    gap: 20px;
}
.card {
    flex: 1;
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 5px 10px rgba(0,0,0,0.1);
}
.card h3 {
    margin: 0;
    color: #2b2b52;
}
h2.section-title {
    margin-top: 40px;
    color: #2b2b52;
}
</style>
</head>
<body>

<div class="sidebar">
    <h3>Admin Panel</h3>
    <div class="profile">
        <img src="https://cdn-icons-png.flaticon.com/512/219/219969.png" alt="Admin">
        <p>Welcome, Admin!</p>
    </div>
    <a href="manage_users.php"><i class="fa fa-users"></i> Manage Users</a>
    <a href="manage_products.php"><i class="fa fa-box"></i> Manage Products</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h1>Admin Dashboard</h1>
    <div class="cards">
        <div class="card">
            <h3>Total Users</h3>
            <p style="font-size:24px; font-weight:bold;"><?= $totalUsers; ?></p>
        </div>
        <div class="card">
            <h3>Total Products</h3>
            <p style="font-size:24px; font-weight:bold;"><?= $totalProducts; ?></p>
        </div>
    </div>
</div>

</body>
</html>
