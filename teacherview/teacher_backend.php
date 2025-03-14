<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
    } else {
        header("Location: ../index.php");
        exit();
    }
}

include 'db_connection.php';

$user_id = $_SESSION['user_id'];

// Retrieve the user's name from the database
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $day = $_POST['day'];
        $startTime = $_POST['startTime'];
        $endTime = $_POST['endTime'];

        $stmt = $conn->prepare("DELETE FROM teacher_schedules WHERE user_id = ? AND day_of_week = ? AND start_time = ? AND end_time = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        $conn->close();

        header("Location: teacher.php");
        exit();
    }

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    foreach ($days as $day) {
        if (isset($_POST[$day . 'StartTime']) && isset($_POST[$day . 'EndTime'])) {
            foreach ($_POST[$day . 'StartTime'] as $index => $startTime) {
                $endTime = $_POST[$day . 'EndTime'][$index];

                if (!empty($startTime) && !empty($endTime)) {
                    // Check if the time slot already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM teacher_schedules WHERE user_id = ? AND day_of_week = ? AND start_time = ? AND end_time = ?");
                    if (!$stmt) {
                        die("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();

                    if ($count == 0) {
                        // Insert new schedule
                        $stmt = $conn->prepare("INSERT INTO teacher_schedules (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)
                                                ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)");
                        if (!$stmt) {
                            die("Prepare failed: " . $conn->error);
                        }
                        $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);

                        if (!$stmt->execute()) {
                            die("Execute failed: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }

    header("Location: teacher.php");
    exit();
}

// Retrieve the updated schedule
$stmt = $conn->prepare("SELECT day_of_week, start_time, end_time FROM teacher_schedules WHERE user_id = ? ORDER BY start_time ASC");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();

$schedule = [];
$scheduleForModal = [];
while ($row = $result->fetch_assoc()) {
    $day = strtolower($row['day_of_week']);
    $timeInterval = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
    $schedule[$timeInterval][$day] = 'Scheduled';
    $scheduleForModal[$day][] = ['start' => $row['start_time'], 'end' => $row['end_time']];
}

$stmt->close();

// Retrieve the registrations for the current teacher
$registrations = [];
$stmt = $conn->prepare("SELECT r.registration_id, r.user_id, r.date, r.start_time, r.end_time, r.reason, r.section, r.status, r.reject_reason, r.physical_meeting, u.firstname, u.middlename, u.lastname FROM registrations r JOIN users u ON r.user_id = u.id WHERE r.teacher = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Automatically approve if physical_meeting is not checked
        if (!isset($row['physical_meeting']) || $row['physical_meeting'] != 1) {
            $row['status'] = 'Approved';
        }
        $registrations[] = $row;
    }
    $stmt->close();
}
$conn->close();

error_log(print_r($registrations, true));