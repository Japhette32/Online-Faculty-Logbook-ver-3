<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['status'])) {
    $status = $_GET['status'];
    $registration_id = $_POST['registration_id'];
    $reject_reason = isset($_POST['rejectReason']) ? $_POST['rejectReason'] : null;

    if ($status === 'Done') {
        $stmt = $conn->prepare("DELETE FROM registrations WHERE registration_id = ?");
        $stmt->bind_param("i", $registration_id);
    } else {
        $stmt = $conn->prepare("UPDATE registrations SET status = ?, reject_reason = ? WHERE registration_id = ?");
        $stmt->bind_param("ssi", $status, $reject_reason, $registration_id);
    }
    
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: teacher.php");
exit();