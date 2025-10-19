<?php
// login.php
session_start();
require_once "connect.php"; // Database connection

class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT login_id, user_id, password, role, status FROM tbl_login WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            // ✅ Check if the account is inactive before verifying password
            if ($row['status'] === 'inactive') {
                echo "<script>alert('Your account has been deactivated by admin. Please contact support.'); window.location='login.php';</script>";
                exit();
            }

            if (password_verify($password, $row['password'])) {
                $_SESSION['login_id'] = $row['login_id'];
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                return $row['role'];
            } else {
                return "Incorrect password";
            }
        } else {
            return "Email not registered";
        }
    }
}

// Database connection
$database = new mysqli("localhost", "root", "", "athleticart");
$user = new User($database);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $role_or_error = $user->login($email, $password);

    if ($role_or_error === 'admin') {
        header("Location: admindashboard.php");
        exit();
    } elseif ($role_or_error === 'user') {
        header("Location: userindex.php");
        exit();
    } else {
        $message = $role_or_error; // Show error message
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f0f0; }
.container { width: 400px; margin: 50px auto; background: #fff; padding: 25px; box-shadow: 0 0 10px #aaa; border-radius: 8px; }
h2 { text-align: center; margin-bottom: 20px; }
input[type="email"], input[type="password"] { width: 100%; padding: 12px; margin: 8px 0; box-sizing: border-box; }
input[type="submit"] { width: 100%; padding: 12px; margin-top: 12px; background: #007bff; color: #fff; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; }
input[type="submit"]:hover { background: #0056b3; }
.error { color: red; font-size: 14px; margin: 2px 0 8px 0; }
.success { color: green; font-size: 14px; margin: 2px 0 8px 0; }
</style>

<!-- jQuery & jQuery Validate -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>
<script>
$(document).ready(function() {
    $("#loginForm").validate({
        rules: {
            email: {
                required: true,
                email: true
            },
            password: {
                required: true,
                minlength: 6
            }
        },
        messages: {
            email: {
                required: "Please enter your email address",
                email: "Please enter a valid email"
            },
            password: {
                required: "Please enter your password",
                minlength: "Password must be at least 6 characters long"
            }
        },
        errorClass: "error",
        errorElement: "div",
        errorPlacement: function(error, element) {
            error.insertAfter(element);
        },
        highlight: function(element) {
            $(element).css('border-color', 'red');
        },
        unhighlight: function(element) {
            $(element).css('border-color', '');
        },
        submitHandler: function(form) {
            form.submit();
        }
    });
});
</script>
</head>
<body>

<div class="container">
    <h2>Login</h2>
    <?php if ($message != ""): ?>
        <div class="error"><?= $message ?></div>
    <?php endif; ?>
    <form id="loginForm" method="POST" action="">
        <input type="email" name="email" placeholder="Email">
        <input type="password" name="password" placeholder="Password">
        <input type="submit" value="Login">
    </form>
</div>

</body>
</html>
