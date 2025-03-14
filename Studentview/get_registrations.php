<?php
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "faculty_logbook";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_GET['user_id'];

$sql = "SELECT * FROM registrations WHERE user_id = '$user_id'";
$result = $conn->query($sql);

$registrations = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
}

echo json_encode($registrations);

$conn->close();
?>