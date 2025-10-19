<?php
// register.php
session_start();
require_once "connect.php"; // Make sure this connects to your database

class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $phone_no, $email, $password, $role = 'user') {
        // Check if email already exists in tbl_login
        $stmt = $this->conn->prepare("SELECT login_id FROM tbl_login WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0){
            return "Email already registered";
        }

        // Insert into tbl_user
        $stmt = $this->conn->prepare("INSERT INTO tbl_user (name, phone_no) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $phone_no);
        if($stmt->execute()){
            $user_id = $stmt->insert_id; // get generated user_id

            // Insert into tbl_login
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $this->conn->prepare("INSERT INTO tbl_login (user_id, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("isss", $user_id, $email, $hashed_password, $role);
            if($stmt2->execute()){
                return true;
            } else {
                return "Registration failed at login table";
            }
        } else {
            return "Registration failed at user table";
        }
    }
}

// Database connection
$database = new mysqli("localhost", "root", "", "athleticart"); // updated database name
$user = new User($database);

$message = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    $phone_no = $_POST['phone_no'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $user->register($name, $phone_no, $email, $password);
    if($result === true){
        $message = "Registration successful!";
    } else {
        $message = $result;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registration</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f0f0f0;
}
.container {
    width: 400px;
    margin: 50px auto;
    background: #fff;
    padding: 20px;
    box-shadow: 0px 0px 10px #aaa;
    border-radius: 5px;
}
h2 {
    text-align: center;
}
form input[type="text"], form input[type="email"], form input[type="password"] {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
}
form input[type="submit"] {
    width: 100%;
    padding: 10px;
    background: #28a745;
    color: #fff;
    border: none;
    cursor: pointer;
}
form input[type="submit"]:hover {
    background: #218838;
}
.error {
    color: red;
    margin-bottom: 10px;
}
.success {
    color: green;
    margin-bottom: 10px;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>
<script>
$(document).ready(function(){
    $("#registrationForm").validate({
        rules: {
            name: {
                required: true,
                minlength: 3,
                maxlength: 50
            },
            phone_no: {
                required: true,
                digits: true,
                minlength: 10,
                maxlength: 15
            },
            email: {
                required: true,
                email: true
            },
            password: {
                required: true,
                minlength: 6,
                maxlength: 20
            }
        },
        messages: {
            name: {
                required: "Please enter your name",
                minlength: "Name must be at least 3 characters",
                maxlength: "Name cannot exceed 50 characters"
            },
            phone_no: {
                required: "Please enter your phone number",
                digits: "Phone number must contain only digits",
                minlength: "Phone number must be at least 10 digits",
                maxlength: "Phone number cannot exceed 15 digits"
            },
            email: {
                required: "Please enter your email",
                email: "Please enter a valid email address"
            },
            password: {
                required: "Please provide a password",
                minlength: "Password must be at least 6 characters",
                maxlength: "Password cannot exceed 20 characters"
            }
        },
        errorClass: "error",
        submitHandler: function(form) {
            form.submit();
        }
    });
});
</script>
</head>
<body>

<div class="container">
    <h2>Register</h2>
    <?php if($message != ""): ?>
        <p class="<?= $result === true ? 'success' : 'error'; ?>"><?= $message; ?></p>
    <?php endif; ?>
    <form id="registrationForm" method="POST" action="">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="phone_no" placeholder="Phone Number" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="submit" value="Register">
        <p><a href="login.php">Login</a></p>
    </form>
</div>
<!-- JQuery Validation -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>


    <script>
    $(document).ready(function () {
        jQuery.validator.addMethod('lettersonly', function (value, element) {
            return /^[^-\s][a-zA-Z_\s-]+$/.test(value);
        }, "Please use letters only.");


        jQuery.validator.addMethod('customEmail', function (value, element) {
            return /^[^0-9][a-zA-Z0-9._%+-]+@(gmail|yahoo|mca.ajce)(\.com|\.in)$/i.test(value);
        }, "Invalid email format.");


        jQuery.validator.addMethod('strongPassword', function (value, element) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?\":{}|<>]).{8,}$/.test(value);
        }, "Weak password.");


        jQuery.validator.addMethod('indianPhone', function (value, element) {
            return /^[6-9]\d{9}$/.test(value);
        }, "Invalid Indian number.");


        $('#myform').validate({
            rules: {
                name: { required: true, lettersonly: true, minlength: 3 },
                email: { required: true, email: true, customEmail: true },
                phone_no: { required: true, digits: true, minlength: 10, maxlength: 10, indianPhone: true },
                pass: { required: true, minlength: 8, strongPassword: true },
                conpass: { required: true, equalTo: "#pass" }
            },
            messages: {
                name: { required: "Enter name", lettersonly: "Only letters allowed" },
                email: { required: "Enter email", email: "Invalid email" },
                phone_no: { required: "Enter phone", digits: "Numbers only" },
                pass: { required: "Enter password", minlength: "At least 8 chars" },
                conpass: { required: "Re-enter password", equalTo: "Passwords don't match" }
            }
        });
    });
    </script>

</body>
</html>
