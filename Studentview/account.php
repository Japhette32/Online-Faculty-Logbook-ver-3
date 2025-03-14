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

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();

// Get information from the registration tab
$registrations = [];
$stmt = $conn->prepare("SELECT r.registration_id, r.date, r.start_time, r.end_time, r.reason, r.status, r.cancel_reason, r.physical_meeting, u.firstname, u.middlename, u.lastname FROM registrations r JOIN users u ON r.teacher = u.id WHERE r.user_id = ?");
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

// Filter registrations for the current week
$currentWeekStart = new DateTime();
$currentWeekStart->modify('monday this week');
$currentWeekEnd = clone $currentWeekStart;
$currentWeekEnd->modify('sunday this week');

$weeklyRegistrations = array_filter($registrations, function($registration) use ($currentWeekStart, $currentWeekEnd) {
    $registrationDate = new DateTime($registration['date']);
    return $registrationDate >= $currentWeekStart && $registrationDate <= $currentWeekEnd;
});

// Filter registrations for the current month
$currentMonthStart = new DateTime('first day of this month');
$currentMonthEnd = new DateTime('last day of this month');

$monthlyRegistrations = array_filter($registrations, function($registration) use ($currentMonthStart, $currentMonthEnd) {
    $registrationDate = new DateTime($registration['date']);
    return $registrationDate >= $currentMonthStart && $registrationDate <= $currentMonthEnd;
});

// Check for changes and store the current state in the session
$previousRegistrations = isset($_SESSION['registrations']) ? $_SESSION['registrations'] : [];
$_SESSION['registrations'] = $registrations;

// Determine if there are new updates
$lastSeenUpdate = isset($_SESSION['last_seen_update']) ? $_SESSION['last_seen_update'] : 0;
$currentUpdateTimestamp = time();
$hasNewUpdates = false;

foreach ($registrations as $registration) {
    $registrationTimestamp = strtotime($registration['date'] . ' ' . $registration['start_time']);
    if ($registrationTimestamp > $lastSeenUpdate) {
        $hasNewUpdates = true;
        break;
    }
}

// Update the last seen update timestamp
if ($hasNewUpdates) {
    $_SESSION['last_seen_update'] = $currentUpdateTimestamp;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Schedule</title>
    <link rel="stylesheet" href="account.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .fade-in {
            opacity: 0;
            transition: opacity 0.6s ease-in;
        }

        .fade-in.show {
            opacity: 1;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            z-index: 1000;
            display: flex;
            align-items: center;
        }

        .notification.show {
            opacity: 1;
        }

        .notification .close-btn {
            margin-left: 10px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body class="fade-in">
<div class="nav">
    <img src="Logo.png" alt="Umak Logo">
    <img src="OSHO-LOGO.webp" alt="OSHO logo">
    <h2>Online Faculty Logbook</h2>
    <div class="line"></div>
    
    <div class="hamburger-menu">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </div>

    <ul data-title="Online Faculty Logbook">
        <li><a href="account.php" class="active">Your Schedule</a></li>
        <li><a href="registration.php">Registration</a></li>
        <li><a href="facultymap.html">Faculty Map</a></li>
        <li class="mobile-logout"><a href="../index.php">Log Out</a></li>
    </ul>
    <a href="../index.php" class="logout-btn">Log Out</a>
</div>
    <div class="container">
        <div class="account-info">
        <h2 class="account-name">Welcome, <br class="mobile-break"><?php echo htmlspecialchars($name); ?></h2>
        <p class="weeklyshcedule">Weekly Consultation Schedule</p>
            <div class="scrollable-table">
                <table class="consultation-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                        $schedule = [];

                        foreach ($weeklyRegistrations as $registration) {
                            $day = date('l', strtotime($registration['date']));
                            $time = date('g:i A', strtotime($registration['start_time'])) . ' - ' . date('g:i A', strtotime($registration['end_time']));
                            $schedule[$time][$day][] = $registration;
                        }

                        uksort($schedule, function ($a, $b) {
                            $timeA = DateTime::createFromFormat('g:i A', explode(' - ', $a)[0]);
                            $timeB = DateTime::createFromFormat('g:i A', explode(' - ', $b)[0]);
                            return $timeA <=> $timeB;
                        });

                        $timeIntervals = array_keys($schedule);
                        $rowCount = 0;

                        foreach ($timeIntervals as $timeInterval) {
                            echo '<tr>';
                            echo '<td>' . $timeInterval . '</td>';
                            foreach ($days as $day) {
                                echo '<td>';
                                if (isset($schedule[$timeInterval][$day])) {
                                    foreach ($schedule[$timeInterval][$day] as $entry) {
                                        $formattedDate = date('F j, Y', strtotime($entry['date']));
                                        $formattedStartTime = date('h:i A', strtotime($entry['start_time']));
                                        $formattedEndTime = date('h:i A', strtotime($entry['end_time']));
                                        $status = htmlspecialchars($entry['status']);
                                        $cancelReason = htmlspecialchars($entry['cancel_reason']);
                                        $teacherFullName = htmlspecialchars($entry['lastname']) . ', ' . htmlspecialchars($entry['firstname']);
                                        if (!empty($entry['middlename'])) {
                                            $teacherFullName .= ', ' . htmlspecialchars($entry['middlename']);
                                        }
                                        echo '<div class="tooltip-container" data-registration-id="' . $entry['registration_id'] . '" onclick="showPopup(this, \'' . $teacherFullName . '\', \'' . $formattedDate . '\', \'' . $formattedStartTime . ' - ' . $formattedEndTime . '\', \'' . htmlspecialchars($entry['reason']) . '\', \'' . $status . '\', \'' . $cancelReason . '\')">';
                                        echo htmlspecialchars($entry['reason']);
                                        echo '</div>';
                                    }
                                }
                                echo '</td>';
                            }
                            echo '</tr>';
                            $rowCount++;
                        }

                        while ($rowCount < 5) {
                            echo '<tr>';
                            echo '<td></td>';
                            foreach ($days as $day) {
                                echo '<td></td>';
                            }
                            echo '</tr>';
                            $rowCount++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="account-info-footer">
    <div class="remarks-container">
    <p class="remark">* Refresh for latest schedule updates</p>
        <p class="mark">* Click to see teacher, date, and time</p>
    </div>
</div>
</div>
    </div>
    <div class="calendar-container">
        <h3 class="calendar-title">Monthly Schedule</h3>
        <h3 class="calendar-title">Current Month: <?php echo date('F Y'); ?></h3>
        <div class="calendar">
            <?php
            $currentMonth = new DateTime();
            $currentMonth->modify('first day of this month');
            $daysInMonth = $currentMonth->format('t');
            $firstDayOfMonth = $currentMonth->format('N') - 1;

            $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

            foreach ($daysOfWeek as $day) {
                echo "<div class='calendar-header'>$day</div>";
            }

            for ($i = 0; $i < $firstDayOfMonth; $i++) {
                echo "<div class='calendar-day'></div>";
            }

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $currentMonth->format('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                echo "<div class='calendar-day'>";
                echo "<div class='date'>$day</div>";

                foreach ($monthlyRegistrations as $registration) {
                    if ($registration['date'] == $date) {
                        $formattedDate = date('F j, Y', strtotime($registration['date']));
                        $formattedStartTime = date('g:i A', strtotime($registration['start_time']));
                        $formattedEndTime = date('g:i A', strtotime($registration['end_time']));
                        $status = htmlspecialchars($registration['status']);
                        $cancelReason = htmlspecialchars($registration['cancel_reason']);
                        $teacherFullName = htmlspecialchars($registration['lastname']).','.htmlspecialchars($registration['firstname']);
                        if (!empty($registration['middlename'])) {
                            $teacherFullName .= ',' . htmlspecialchars($registration['middlename']);
                        }
                        echo "
                        <div class='tooltip-container' data-registration-id='{$registration['registration_id']}' onclick='showPopup(this, \"{$teacherFullName}\", \"{$formattedDate}\", \"{$formattedStartTime} - {$formattedEndTime}\", \"{$registration['reason']}\", \"{$status}\", \"{$cancelReason}\")'>
                            <span class='reason-text'>{$registration['reason']}</span>
                        </div>
                        ";
                    }
                }

                echo "</div>";
            }
            ?>
        </div>
    </div>

    <div id="cancelReasonModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeCancelReasonModal()">&times;</span>
            <h2 class="modal-title">Cancel Consultation</h2>
            <form id="cancelReasonForm" method="POST" action="update_status.php?status=Cancelled" onsubmit="submitCancelReason(event)">
                <input type="hidden" name="registration_id" id="cancelRegistrationId">
                <label for="cancelReason">Please provide a reason for cancellation:</label>
                <textarea id="cancelReason" name="cancelReason" rows="4" cols="50" required></textarea>
                <button type="submit" class="submit-cancel-reason-btn">Submit</button>
            </form>
        </div>
    </div>

    <!-- Notification element -->
    <div id="notification" class="notification">
        <span id="notificationMessage"></span>
        <span class="close-btn" onclick="closeNotification()">×</span>
    </div>

    <script>
        var hasNewUpdates = <?php echo json_encode($hasNewUpdates); ?>;
    </script>
    <script src="account.js"></script>
    <footer class="footer">
    <div class="info">
        <div class="ohsologo-container">
            <h2>Occupational Health and Safety Office</h2>
            <img src="OSHO-LOGO.webp" alt="OHSO Logo" class="ohsologo">
        </div>
        <div class="contact-info">
            <h2>Contact OHSO</h2>
            <ul>
                <li>
                    <img src="gmail.png" alt="Gmail Icon">
                    <span>ohso@umak.edu.ph</span>
                </li>
                <li>
                    <img src="phone-call.png" alt="Phone Icon">
                    <span>288820535</span>
                </li>
                <li>
                    <img src="facebook.png" alt="Facebook Icon">
                    <a href="https://www.facebook.com/profile.php?id=100076383932855"><span>UMak Occupational Health and Safety Office </span></a>
                </li>
            </ul>
        </div>
        <div class="location-info">
            <h2>Ohso Office</h2>
            <ul>
                <li>
                    <img src="map.png" alt="Map Icon">
                    <span>J.P. Rizal Extn. West Rembo, Makati, Philippines, 1215</span>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
       <!-- <div class="credits">
            <p>Dito lalagay credits.</p>
        </div> -->
        <div class="copyright">
            <p>&copy; <?php echo date("Y"); ?> Online Faculty Logbook. All rights reserved. Icons and code used are copyrighted by their respective owners.</p>
        </div>
    </div>
</footer>
</body>
</html>