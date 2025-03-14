<?php
session_start();

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
}

$user_id = $_SESSION['user_id'];

include 'db_connection.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax']) && $_POST['ajax'] === 'load_schedule' && isset($_POST['teacher_id'])) {
        $selected_teacher_id = $_POST['teacher_id'];
        $schedules = [];
        $stmt = $conn->prepare("SELECT day_of_week, TIME_FORMAT(start_time, '%h:%i %p') AS start_time, TIME_FORMAT(end_time, '%h:%i %p') AS end_time FROM teacher_schedules WHERE user_id = ? ORDER BY start_time ASC");
        if ($stmt) {
            $stmt->bind_param("i", $selected_teacher_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $schedules[] = $row;
            }
            $stmt->close();
        }
        echo json_encode($schedules);
        exit();
    }

    $section = $_POST['section'];
    $teacher = $_POST['teacher'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $reason = $_POST['reason'];
    $physical_meeting = isset($_POST['physical_meeting']) ? 1 : 0;

    $stmt = $conn->prepare("SELECT firstname, middlename, lastname FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($firstname, $middlename, $lastname);
        $stmt->fetch();
        $stmt->close();
    }

    $name = $lastname . ", " . $firstname . (!empty($middlename) ? " " . $middlename : "");

    if (empty($user_id) || empty($firstname) || empty($lastname) || empty($section) || empty($teacher) || empty($date) || empty($start_time) || empty($end_time) || empty($reason)) {
        $error_message = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("INSERT INTO registrations (user_id, name, section, teacher, date, start_time, end_time, reason, physical_meeting) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssssssi", $user_id, $name, $section, $teacher, $date, $start_time, $end_time, $reason, $physical_meeting);
            if ($stmt->execute()) {
                $success_message = 'Registration successful!';
            } else {
                $error_message = 'Error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = 'Prepare failed: ' . $conn->error;
        }
    }
}

$teachers = [];
$stmt = $conn->prepare("SELECT id, firstname, middlename, lastname FROM users WHERE role = 'teacher' ORDER BY lastname ASC");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($teacher_id, $firstname, $middlename, $lastname);
    while ($stmt->fetch()) {
        $fullname = $lastname . ", " . $firstname;
        if (!empty($middlename)) {
            $fullname .= " " . $middlename;
        }
        $teachers[] = ['id' => $teacher_id, 'name' => $fullname];
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="registration.css">
    <link rel="stylesheet" href="select2.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .fade-in {
            opacity: 0;
            transition: opacity 0.6s ease-in;
        }

        .fade-in.show {
            opacity: 1;
        }

        .select2-container .select2-selection--single {
            height: 50px;
            margin-bottom: 25px;
            border-radius: 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 50px;
            font-size: 1.5em;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 50px;
        }

        .select2-search__field {
            height: 50px;
            font-size: 20px;
            padding-left: 30px;
            background: url('search.png') no-repeat right center;
            background-size: 45px 45px;
        }

        .input-group select {
            margin-bottom: 15px;
        }

        #registerAgain {
            display: none;
            margin-top: 20px;
        }

        #responseMessage {
            margin-top: 20px;
            font-size: 1.5em;
            color: green;
            text-align: center;
        }

        .button-container {
            text-align: center;
            margin-top: 20px;
        }

        .button-container button {
            display: inline-block;
            margin: 0 auto;
        }

        #scheduleList {
            font-size: 1.2em;
            list-style-type: none;
            margin-right: 40px;
        }

        .select2-results__option {
            font-size: 20px;
            padding: 10px;
        }

        .time-inputs {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .time-inputs input[type="time"] {
            margin: 5px 0;
        }

        .time-inputs span {
            margin: 5px 0;
        }

        .success-message {
            color: green;
            font-size: 1.5em;
            text-align: center;
            margin-bottom: 20px;
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

        <ul>
            <h2 class="Title">Online Faculty Logbook</h2>
            <li><a href="account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>">Your Schedule</a></li>
            <li><a href="registration.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'registration.php' ? 'active' : ''; ?>">Registration</a></li>
            <li><a href="facultymap.html">Faculty Map</a></li>
            <li class="mobile-logout"><a href="../index.php">Log Out</a></li>
        </ul>

        <a href="../index.php" class="logout-btn">Log Out</a>
    </div>
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <div id="responseMessage" class="hidden">
            <?php if (isset($success_message)) echo $success_message; ?>
            <?php if (isset($error_message)) echo $error_message; ?>
        </div>
        <h2>Registration Form</h2>
        <form id="registrationForm" method="POST" action="">
            <div class="input-group">
                <label for="section">Section:</label>
                <input type="text" id="section" name="section" required placeholder="G12 - 01 CPG">

                <label for="teacher">Teacher:</label>
                <select id="teacher" name="teacher" data-placeholder="Select a Teacher" required>
                    <option value="" disabled selected>Select a Teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group radio-container">
                <label>Request a physical meeting with the teacher?</label>
                <div>
                    <label for="physical_meeting_yes">Yes</label>
                    <input type="radio" id="physical_meeting_yes" name="physical_meeting" value="1">
                    <label for="physical_meeting_no">No</label>
                    <input type="radio" id="physical_meeting_no" name="physical_meeting" value="0" checked>
                </div>
            </div>
            <div id="schedule" style="display: none;">
                <h3>Available Schedule</h3>
                <ul id="scheduleList"></ul>
            </div>
            <div class="input-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>
            <div class="input-group time-inputs">
                <label for="start_time">Start Time:</label>
                <input type="time" id="start_time" name="start_time" required>
                <label for="end_time">End Time:</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>
            <div class="input-group">
                <label for="reason">Reason for consultation:</label>
                <input type="text" id="reason" name="reason" required>
            </div>
            <div class="button-container">
                <button type="submit" id="registerButton">Register</button>
                <button type="button" id="registerAgain" onclick="resetForm()">Register Again</button>
            </div>
            <input type="hidden" id="userId" name="userId" value="<?php echo htmlspecialchars($user_id); ?>">
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#teacher').select2({
                width: '100%',
                placeholder: 'Select a Teacher',
                allowClear: true,
                language: {
                    inputTooShort: function() {
                        return 'Search here';
                    },
                    searching: function() {
                        return 'Searching...';
                    },
                    noResults: function() { 
                        return 'No results found';
                    }
                }
            });

            $('#teacher').on('change', function() {
                var teacherId = $(this).val();
                if (teacherId) {
                    $.post('registration.php', { ajax: 'load_schedule', teacher_id: teacherId }, function(response) {
                        var schedules = JSON.parse(response);
                        var scheduleList = $('#scheduleList');
                        scheduleList.empty();
                        if (schedules.length > 0) {
                            $('#schedule').show();
                            var scheduleMap = {};
                            schedules.forEach(function(schedule) {
                                if (!scheduleMap[schedule.day_of_week]) {
                                    scheduleMap[schedule.day_of_week] = [];
                                }
                                scheduleMap[schedule.day_of_week].push(schedule.start_time + ' - ' + schedule.end_time);
                            });
                            for (var day in scheduleMap) {
                                scheduleList.append('<li>' + day + ': ' + scheduleMap[day].join(', ') + '</li>');
                            }
                        } else {
                            $('#schedule').hide();
                        }
                    });
                } else {
                    $('#schedule').hide();
                }
            });
        });

        function resetForm() {
            document.getElementById('registrationForm').reset();
            document.getElementById('responseMessage').classList.add('hidden');
            document.getElementById('registerButton').style.display = 'inline-block';
            document.getElementById('registerAgain').style.display = 'none';
        }
    </script>
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
                    }, 600); 
                });
            });
        });
    </script>
</body>

</html>