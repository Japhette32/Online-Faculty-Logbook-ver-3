<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $otp = rand(100000, 999999);

    $stmt = $conn->prepare("INSERT INTO otp_verification (email, otp) VALUES (?, ?) ON DUPLICATE KEY UPDATE otp = ?");
    $stmt->bind_param("sss", $email, $otp, $otp);

    if ($stmt->execute()) {
        mail($email, "Your OTP Code", "Your OTP code is: $otp");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>