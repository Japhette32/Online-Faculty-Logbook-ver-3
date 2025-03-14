<?php
include 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = $_POST['registration_id'];
    $status = $_GET['status'];
    $cancelReason = isset($_POST['cancelReason']) ? $_POST['cancelReason'] : '';

    if ($status === 'Cancelled') {
        $stmt = $conn->prepare("DELETE FROM registrations WHERE registration_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $registration_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Registration cancelled and removed successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        }
    } else {
        $stmt = $conn->prepare("UPDATE registrations SET status = ?, cancel_reason = ? WHERE registration_id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $status, $cancelReason, $registration_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        }
    }
}

$conn->close();
?>