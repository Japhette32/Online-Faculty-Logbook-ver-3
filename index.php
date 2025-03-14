<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="b.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
            padding-right: 40px; 
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .toggle-password img {
            width: 35px; 
            height: 35px; 
        }
    </style>
    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            var toggleIcon = document.getElementById("toggleIcon");
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.src = "eye.png";
            } else {
                passwordField.type = "password";
                toggleIcon.src = "visual.png"; 
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="Logo.png" alt="Logo Umak">
            <img src="OSHO-LOGO.webp" alt="Logo OHSO">
        </div>
        <div class="logo"><h2>Welcome to the Online Faculty Logbook!</h2></div>
        <form id="loginForm" method="post" action="index.php">
            <h2>Log in</h2>
            <div class="input-group">
                <input required type="email" name="email" autocomplete="off" class="input" placeholder="Email" set=5>
            </div>
            <div class="input-group password-container">
                <input required type="password" name="password" autocomplete="off" class="password" id="password" placeholder="Password" required+>
                <span class="toggle-password" onclick="togglePassword()">
                    <img src="visual.png" alt="Show Password" id="toggleIcon">
                </span>
            </div>
            <button type="submit">Log in</button>
           <a class="signup" href="signup.php">Don't have an account yet? Sign Up</a> 
        </form>
        <?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'db_connection.php'; 

    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $stmt = $conn->prepare("SELECT id, role, password FROM users WHERE email = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $role, $hashed_password);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        session_start();
        $_SESSION['user_id'] = $id; 
        setcookie("user_id", $id, time() + (86400 * 30), "/");
        
        
        error_log("Login successful: user_id = $id, role = $role"); 

        echo "<script>
            localStorage.setItem('currentUser', JSON.stringify({ id: $id, email: '$email' }));
        </script>";
        if ($role == 'teacher') {
            echo "<script>window.location.href = 'teacherview/teacher.php';</script>";
        } else {
            echo "<script>window.location.href = 'Studentview/account.php';</script>";
        }
    } else {
        error_log("Login failed for email: $email");
        echo "<p style='color:red;'>Wrong email or password</p>";
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>
    </div>
</body>
</html>