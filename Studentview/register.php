<?php
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "faculty_logbook";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\php\logs\php_error_log');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

if (isset($data->userId) && isset($data->section) && isset($data->teacher) && isset($data->date) && isset($data->time) && isset($data->reason)) {
    $user_id = $data->userId;
    $section = $data->section;
    $teacher = $data->teacher;
    $date = $data->date;
    $time = $data->time;
    $reason = $data->reason;

    // Fetch the user's name from the database
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($firstname, $middlename, $lastname);
        $stmt->fetch();
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(["message" => "Prepare failed: " . $conn->error]);
        exit();
    }

    $name = $lastname . ", " . $firstname . (!empty($middlename) ? " " . $middlename : "");

    $stmt = $conn->prepare("INSERT INTO registrations (user_id, name, section, teacher, date, time, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issssss", $user_id, $name, $section, $teacher, $date, $time, $reason);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Registration successful"]);
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(["message" => "Prepare failed: " . $conn->error]);
    }
} else {
    error_log("Invalid input");
    echo json_encode(["message" => "Invalid input"]);
}

$conn->close();
?>