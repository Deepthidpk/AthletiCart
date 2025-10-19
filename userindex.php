<?php
session_start();
require_once "connect.php";

// Ensure $conn exists. If connect.php created a different variable (e.g. $database) use it.
// If neither exists, create a local mysqli connection (fallback).
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($database) && ($database instanceof mysqli)) {
        $conn = $database;
    } elseif (isset($db) && ($db instanceof mysqli)) {
        $conn = $db;
    } else {
        // Fallback - adjust credentials if different on your environment
        $conn = new mysqli("localhost", "root", "", "athleticart");
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
    }
}

class Product {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fetch all active categories
    public function getCategories() {
        $query = "SELECT * FROM tbl_category ";
        $res = $this->conn->query($query);
        if ($res === false) {
            return [];
        }
        return $res;
    }

    // Fetch products (optionally by category)
    public function getProductsByCategory($category_id = null) {
        $query = "SELECT p.product_id, p.product_name, p.pro_description, p.quantity, p.price, 
                         p.product_image, c.category_name 
                  FROM tbl_products p 
                  JOIN tbl_category c ON p.category_id = c.category_id ";
        if ($category_id) {
            $query .= " AND p.category_id=" . intval($category_id);
        }
        $res = $this->conn->query($query);
        if ($res === false) {
            return [];
        }
        return $res;
    }
}

// Create product object
$productObj = new Product($conn);

// Category filter
$category_id = isset($_GET['category']) ? $_GET['category'] : null;
$categories = $productObj->getCategories();
$products = $productObj->getProductsByCategory($category_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sports Store | User Index</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background-color: #f4f6f9;
}

/* Navbar */
.navbar {
    background-color: #2b2b52;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 40px;
}
.navbar h2 {
    font-size: 24px;
    color: #fff;
}
.navbar ul {
    list-style: none;
    display: flex;
    gap: 25px;
}
.navbar ul li {
    display: inline;
}
.navbar ul li a {
    color: white;
    text-decoration: none;
    font-weight: 500;
}
.navbar ul li a:hover {
    color: #00ccff;
}

/* Banner */
.banner {
    width: 100%;
    height: 350px;
    background: url('https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=1500&q=80') center/cover no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #fff;
    text-shadow: 2px 2px 4px #000;
}
.banner h1 {
    font-size: 42px;
    background: rgba(0,0,0,0.5);
    padding: 15px 25px;
    border-radius: 10px;
}

/* About Us */
.about {
    padding: 40px;
    background: #fff;
    text-align: center;
}
.about h2 {
    color: #2b2b52;
}
.about p {
    font-size: 16px;
    color: #555;
    width: 80%;
    margin: 10px auto;
}

/* Category Filter */
.category-filter {
    text-align: center;
    margin: 20px 0;
}
.category-filter a {
    margin: 5px;
    padding: 10px 20px;
    background: #2b2b52;
    color: white;
    border-radius: 5px;
    text-decoration: none;
}
.category-filter a:hover {
    background: #00ccff;
}

/* Product Cards */
.products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
    padding: 40px;
}
.card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: 0.3s;
}
.card:hover {
    transform: translateY(-8px);
}
.card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.card-content {
    padding: 15px;
}
.card-content h3 {
    font-size: 18px;
    margin: 0 0 10px;
    color: #2b2b52;
}
.card-content p {
    font-size: 14px;
    color: #555;
}
.add-cart {
    display: inline-block;
    margin-top: 10px;
    background: #00ccff;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}
.add-cart:hover {
    background: #2b2b52;
}

/* Footer */
footer {
    background: #2b2b52;
    color: white;
    text-align: center;
    padding: 15px 0;
    margin-top: 40px;
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>🏏 AthletiCart</h2>
    <ul>
        <li><a href="userindex.php">Home</a></li>
         <li><a href="products.php">Products</a></li>
        <li><a href="cart.php">Cart</a></li>
        <li><a href="orders.php">Orders</a></li>
         <li><a href="logout.php">Logout</a></li>
       
    </ul>
</div>

<!-- Banner -->
<div class="banner">
    <h1>Welcome to Our Sports World</h1>
</div>

<!-- About -->
<div class="about" id="about">
    <h2>About Us</h2>
    <p>We are passionate about sports! Our store offers top-quality sports equipment including cricket bats, badminton rackets, footballs, and volleyballs — all designed to enhance your game. Shop with us and step up your performance!</p>
</div>

<!-- Category Filter -->
<div class="category-filter">
    <a href="userindex.php">All</a>
    <?php
    if ($categories && is_object($categories)) {
        while ($cat = $categories->fetch_assoc()): ?>
            <a href="?category=<?= intval($cat['category_id']); ?>"><?= htmlspecialchars($cat['category_name']); ?></a>
        <?php endwhile;
    }
    ?>
</div>

<!-- Products Section -->
<div class="products">
    <?php
    if ($products && is_object($products) && $products->num_rows > 0): 
        while ($prod = $products->fetch_assoc()): ?>
        <div class="card">
            <?php
            $imgPath = 'uploads/' . htmlspecialchars($prod['product_image']);
            // If image missing, show placeholder
            if (!empty($prod['product_image']) && file_exists($imgPath)): ?>
                <img src="<?= $imgPath; ?>" alt="<?= htmlspecialchars($prod['product_name']); ?>">
            <?php else: ?>
                <img src="https://via.placeholder.com/400x300?text=No+Image" alt="No Image">
            <?php endif; ?>
            <div class="card-content">
                <h3><?= htmlspecialchars($prod['product_name']); ?></h3>
                <p><?= htmlspecialchars($prod['pro_description']); ?></p>
                <p><strong>Price:</strong> ₹<?= htmlspecialchars($prod['price']); ?></p>
                <button class="add-cart"><i class="fa fa-cart-plus"></i> Add to Cart</button>
            </div>
        </div>
        <?php endwhile;
    else: ?>
        
    <?php endif; ?>
</div>

<footer>
    &copy; <?= date('Y'); ?> Sports Store. All rights reserved.
</footer>

<script>
$(document).ready(function(){
    $(".add-cart").click(function(){
        alert("Added to cart!");
    });
});
</script>

</body>
</html>
