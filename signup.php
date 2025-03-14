<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="b.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
        }
        .name-fields {
            display: flex;
            gap: 10px;
            width: 100%;
        }
        .name-fields .input-group {
            flex: 1;
        }
        .char-counter {
            font-size: 0.8em;
            color: #666;
            margin-top: 2px;
            text-align: right;
        }
        @media (max-width: 1440px) {
            .name-fields {
                flex-direction: column;
                gap: 10px;
            }
        }
        @media (max-width: 1024px) {
            .name-fields {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-signup">
        <div class="logo-container">
            <img src="Logo.png" alt="Logo Umak">
            <img src="OSHO-LOGO.webp" alt="Logo OHSO">
        </div>
        <div class="logo"><h2>Welcome to the Online Faculty Logbook!</h2></div>
        <?php
        session_start();
        require 'vendor/autoload.php';

        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        use PHPMailer\PHPMailer\SMTP;

        include 'db_connection.php';

        $signupSuccess = false;
        $accountExists = false;
        $usernameExists = false;
        $usernameError = false;
        $otpSent = false;
        $invalidEmailDomain = false;

        $teacherEmails = [
            'neariza.digas@umak.edu.ph',
            'ariel.domingo@umak.edu.ph',
            'elvis.galzote@umak.edu.ph'
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['verify_otp'])) {
                $otp = $_POST['otp'];
                $email = $_POST['email'];
                $stmt = $conn->prepare("SELECT otp FROM otp_verification WHERE email = ? AND otp = ?");
                $stmt->bind_param("ss", $email, $otp);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    $stmt = $conn->prepare("DELETE FROM otp_verification WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO users (firstname, middlename, lastname, name, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $_SESSION['firstname'], $_SESSION['middlename'], $_SESSION['lastname'], $_SESSION['username'], $email, $_SESSION['hashedPassword'], $_SESSION['role']);
                    $stmt->execute();
                    $stmt->close();

                    $_SESSION['success_message'] = "Account successfully registered. Go to login.";
                    $_SESSION['signup_success'] = true;
                    header("Location: signup.php");
                    exit();
                } else {
                    echo "<p style='color:red;'>Invalid OTP. Please try again.</p>";
                }
            } else {
                $firstname = $_POST['firstname'];
                $middlename = $_POST['middlename'];
                $lastname = $_POST['lastname'];
                $username = $_POST['name']; 
                $email = $_POST['email'];
                $password = $_POST['password'];

                if (strlen($username) > 10) {
                    $usernameError = true;
                } else {
                    $allowedDomain = 'umak.edu.ph';
                    $emailDomain = substr(strrchr($email, "@"), 1);
                    if ($emailDomain !== $allowedDomain) {
                        $invalidEmailDomain = true;
                    } else {
                        $name = "$firstname $middlename $lastname";

                        if (in_array($email, $teacherEmails)) {
                            $role = 'teacher';
                        } else {
                            $role = 'student';
                        }
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        if (!$conn) {
                            die("Connection failed: " . mysqli_connect_error());
                        }

                        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $stmt->store_result();
                        $emailExists = $stmt->num_rows > 0;
                        $stmt->close();
                        
                        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $stmt->store_result();
                        $usernameExists = $stmt->num_rows > 0;
                        $stmt->close();

                        if ($emailExists) {
                            $accountExists = true;
                            echo "<p style='color:red;'>Email already exists. Please use a different email.</p>";
                        } 
                        elseif ($usernameExists) {
                            $accountExists = true;
                            echo "<p style='color:red;'>Username already taken. Please choose a different username.</p>";
                        }
                        else {
                            $stmt = $conn->prepare("DELETE FROM otp_verification WHERE email = ?");
                            $stmt->bind_param("s", $email);
                            $stmt->execute();
                            $stmt->close();

                            $otp = rand(100000, 999999);
                            $stmt = $conn->prepare("INSERT INTO otp_verification (email, otp) VALUES (?, ?)");
                            $stmt->bind_param("ss", $email, $otp);
                            if ($stmt->execute()) {
                                $_SESSION['firstname'] = $firstname;
                                $_SESSION['middlename'] = $middlename;
                                $_SESSION['lastname'] = $lastname;
                                $_SESSION['username'] = $username;
                                $_SESSION['hashedPassword'] = $hashedPassword;
                                $_SESSION['role'] = $role;

                                $mail = new PHPMailer(true);
                                try {
                                    $mail->isSMTP();
                                    $mail->Host = 'smtp.gmail.com';
                                    $mail->SMTPAuth = true;
                                    $mail->Username = 'onlinefacultylogbook@gmail.com';
                                    $mail->Password = 'ygvs nrls bybc uzsm';
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                    $mail->Port = 587;

                                    $mail->setFrom('your-email@gmail.com', 'No Reply');
                                    $mail->addAddress($email);

                                    $mail->isHTML(true);
                                    $mail->Subject = 'OTP Code Online Faculty Logbook';
                                    $mail->Body    = "Your OTP code is: $otp";

                                    $mail->send();
                                    $otpSent = true;
                                } catch (Exception $e) {
                                    echo "<p style='color:red;'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
                                }
                            } else {
                                echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
                            }
                            $stmt->close();
                        }
                        $conn->close();
                    }
                }
            }
        }

        if (isset($_SESSION['success_message'])) {
            echo "<p style='color:green;'>" . $_SESSION['success_message'] . "</p>";
            unset($_SESSION['success_message']);
        }
        ?>
        <form id="signupForm" method="post" action="signup.php">
            <h2>Sign Up</h2>
            <?php if ($otpSent): ?>
                <div class="input-group">
                    <input required type="text" name="otp" autocomplete="off" class="input" placeholder="Enter OTP">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <button type="submit" name="verify_otp">Verify OTP</button>
            <?php elseif (isset($_SESSION['signup_success']) && $_SESSION['signup_success']): ?>
                <button type="button" onclick="window.location.href='login.php'">Go to Login</button>
            <?php else: ?>
                <div class="input-group">
                    <input required type="email" name="email" autocomplete="off" class="input" placeholder="Email">
                </div>
                <div class="input-group password-container">
                    <input required type="text" name="password" autocomplete="off" class="password" id="password" placeholder="Password">
                </div>
                <div class="name-fields">
                    <div class="input-group">
                        <input required type="text" name="firstname" autocomplete="off" class="input" placeholder="First Name">
                    </div>
                    <div class="input-group">
                        <input type="text" name="middlename" autocomplete="off" class="input" placeholder="Middle Name (Optional)">
                    </div>
                    <div class="input-group">
                        <input required type="text" name="lastname" autocomplete="off" class="input" placeholder="Last Name">
                    </div>
                </div>
                <div class="input-group">
                    <input required type="text" name="name" id="username" autocomplete="off" class="input" placeholder="Username" maxlength="10" oninput="countCharacters()">
                    <div class="char-counter"><span id="char-count">0</span>/10 characters</div>
                    <?php if ($usernameError): ?>
                        <p style="color:red;margin-top:2px;">Username must be 10 characters or less.</p>
                    <?php endif; ?>
                </div>
                <button type="submit">Sign Up</button>
                <?php if ($invalidEmailDomain): ?>
                    <p style="color:red;margin-top:2px;">Please Use Your Umak Email</p>
                <?php endif; ?>
            <?php endif; ?>
        </form>
    </div>
    <script>
        function countCharacters() {
            var username = document.getElementById("username");
            var charCount = document.getElementById("char-count");
            charCount.textContent = username.value.length;
            
            if (username.value.length > 10) {
                charCount.style.color = "#ff6600";
            } else {
                charCount.style.color = "#666";
            }
        }
        
        window.onload = function() {
            countCharacters();
        }
    </script>
</body>
</html>