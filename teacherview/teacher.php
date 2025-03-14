<?php
include 'teacher_backend.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Schedule</title>
    <link rel="stylesheet" href="teacher.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        /* Fade-in effect */
        .fade-in {
            opacity: 0;
            transition: opacity 0.3s ease-in;
        }

        .fade-in.show {
            opacity: 1;
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
            <h2 class="Title">Online Faculty Logbook</h2>
            <li><a href="teacher.php" class="active">Your Schedule</a></li>
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
                            <th class="remove-column" style="display: none;">Remove</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <?php
                        // Generate the table rows
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                        $timeIntervals = array_keys($schedule);

                        // Ensure there are always 5 rows
                        for ($i = 0; $i < 5; $i++) {
                            echo '<tr>';
                            if (isset($timeIntervals[$i])) {
                                $timeInterval = $timeIntervals[$i];
                                echo '<td>' . $timeInterval . '</td>';
                                foreach ($days as $day) {
                                    echo '<td>';
                                    $hasSchedule = false;
                                    if (isset($schedule[$timeInterval][$day])) {
                                        foreach ($registrations as $entry) {
                                            if (isset($entry['physical_meeting']) && $entry['physical_meeting'] == 1) {
                                                $entryDay = strtolower(date('l', strtotime($entry['date'])));
                                                $entryStartTime = date('g:i A', strtotime($entry['start_time']));
                                                $entryEndTime = date('g:i A', strtotime($entry['end_time']));
                                                $startTime = date('g:i A', strtotime(explode(' - ', $timeInterval)[0]));
                                                $endTime = date('g:i A', strtotime(explode(' - ', $timeInterval)[1]));

                                                if ($entryDay == $day && $entryStartTime >= $startTime && $entryEndTime <= $endTime) {
                                                    $formattedDate = date('F j, Y', strtotime($entry['date']));
                                                    $formattedStartTime = date('h:i A', strtotime($entry['start_time']));
                                                    $formattedEndTime = date('h:i A', strtotime($entry['end_time']));
                                                    $status = htmlspecialchars($entry['status']);
                                                    $rejectReason = htmlspecialchars($entry['reject_reason']);
                                                    $studentName = htmlspecialchars($entry['lastname']) . ', ' . htmlspecialchars($entry['firstname']) . (!empty($entry['middlename']) ? ', ' . htmlspecialchars($entry['middlename']) : '');
                                                    echo '<div class="tooltip-container" onclick="popup(this, \'' . $studentName . '\', \'' . $formattedDate . '\', \'' . $formattedStartTime . ' - ' . $formattedEndTime . '\', \'' . htmlspecialchars($entry['reason']) . '\', \'' . htmlspecialchars($entry['section']) . '\', ' . $entry['registration_id'] . ', \'' . $status . '\', \'' . $rejectReason . '\')">';
                                                    echo '<span class="reason">' . htmlspecialchars($entry['reason']) . '</span>';
                                                    echo '</div>';
                                                    $hasSchedule = true;
                                                    break; // Exit the loop once a matching registration is found
                                                }
                                            }
                                        }
                                    }
                                    if (!$hasSchedule && isset($schedule[$timeInterval][$day])) {
                                        echo 'Scheduled';
                                    }
                                    echo '</td>';
                                }
                            } else {
                                echo '<td></td>'; // Empty time cell
                                // Add empty cells for each day
                                foreach ($days as $day) {
                                    echo '<td></td>';
                                }
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="account-info-footer">
                <button id="editScheduleBtn" onclick="openModal()">Edit Schedule</button>
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

                foreach ($registrations as $entry) {
                    if (date('Y-m-d', strtotime($entry['date'])) == $date && isset($entry['physical_meeting']) && $entry['physical_meeting'] == 1) {
                        $formattedStartTime = date('h:i A', strtotime($entry['start_time']));
                        $formattedEndTime = date('h:i A', strtotime($entry['end_time']));
                        $status = htmlspecialchars($entry['status']);
                        $rejectReason = htmlspecialchars($entry['reject_reason']);
                        $studentName = htmlspecialchars($entry['lastname']) . ', ' . htmlspecialchars($entry['firstname']) . (!empty($entry['middlename']) ? ', ' . htmlspecialchars($entry['middlename']) : '');
                        echo '<div class="tooltip-container" onclick="popup(this, \'' . $studentName . '\', \'' . date('F j, Y', strtotime($entry['date'])) . '\', \'' . $formattedStartTime . ' - ' . $formattedEndTime . '\', \'' . htmlspecialchars($entry['reason']) . '\', \'' . htmlspecialchars($entry['section']) . '\', ' . $entry['registration_id'] . ', \'' . $status . '\', \'' . $rejectReason . '\')">';
                        echo '<span class="reason">' . htmlspecialchars($entry['reason']) . '</span><br>';
                        echo '</div>';
                    }
                }

                echo "</div>";
            }
            ?>
        </div>
    </div>
    <!-- Reject Reason Modal -->
    <div id="rejectReasonModal" class="modal reject-reason-modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeRejectReasonModal()">&times;</span>
            <h2 class="modal-title">Reject Consultation</h2>
            <form id="rejectReasonForm" method="POST" action="update_status.php?status=Rejected">
                <input type="hidden" name="registration_id" id="rejectRegistrationId">
                <label for="rejectReason">Please provide a reason for rejection:</label>
                <textarea id="rejectReason" name="rejectReason" rows="4" cols="50" required></textarea>
                <button type="submit" class="submit-reject-reason-btn">Submit</button>
            </form>
        </div>
    </div>
    <!-- Edit Schedule Popup -->
    <div id="editScheduleModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 class="modal-title">Edit Schedule</h2>
            <form id="editScheduleForm" method="POST" action="teacher.php">
                <table class="schedule-table">
                    <tr>
                        <th>Day</th>
                        <th>Time Slot</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($days as $day) {
                        echo '<tr>';
                        echo '<td>' . ucfirst($day) . '</td>';
                        echo '<td id="' . $day . 'Inputs">';
                        if (isset($scheduleForModal[$day]) && !empty($scheduleForModal[$day])) {
                            foreach ($scheduleForModal[$day] as $time) {
                                echo '<div class="time-inputs">';
                                echo '<input type="time" name="' . $day . 'StartTime[]" value="' . $time['start'] . '">';
                                echo ' - ';
                                echo '<input type="time" name="' . $day . 'EndTime[]" value="' . $time['end'] . '">';
                                echo '<button type="button" class="btn-remove" onclick="removeTime(this, \'' . $day . '\', \'' . $time['start'] . '\', \'' . $time['end'] . '\')">-</button>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="time-inputs">';
                            echo '<input type="time" name="' . $day . 'StartTime[]" value="">';
                            echo ' - ';
                            echo '<input type="time" name="' . $day . 'EndTime[]" value="">';
                            echo '<button type="button" class="btn-remove" onclick="removeTime(this, \'' . $day . '\', \'\', \'\')">-</button>';
                            echo '</div>';
                        }
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" class="btn-add" onclick="addTime(\'' . ucfirst($day) . '\')">+</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
                <button type="submit" class="save-btn">Save Schedule</button>
            </form>
        </div>
    </div>
    <script src="teacher.js"></script>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector('.hamburger-menu');
            const navMenu = document.querySelector('.nav ul');

            hamburger.addEventListener('click', function() {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
            });


            document.querySelectorAll('.nav ul li a').forEach(link => {
                link.addEventListener('click', function() {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                });
            });

            document.body.classList.add('show');

            document.querySelectorAll('.nav ul li a, .logout-btn').forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    const href = this.getAttribute('href');
                    document.body.classList.remove('show');
                    setTimeout(() => {
                        window.location.href = href;
                    }, 500); 
                });
            });
        });
    </script>
</body>

</html>