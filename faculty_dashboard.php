<?php
session_start();

// Check if the user is logged in and is a faculty member
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Faculty') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Tresmagia SmartLock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Faculty Dashboard</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        <p class="lead">Here is your faculty dashboard where you can manage attendance, schedules, and more.</p>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Today's Schedule
                    </div>
                    <div class="card-body">
                        <p>No classes scheduled for today. (Placeholder)</p>
                        <!-- You can fetch and display today's schedule dynamically here -->
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        Attendance Overview
                    </div>
                    <div class="card-body">
                        <p>Present: 20</p>
                        <p>Absent: 5</p>
                        <p>Late: 2</p>
                        <!-- These can be dynamically generated based on the database -->
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        Announcements
                    </div>
                    <div class="card-body">
                        <p>No new announcements.</p>
                        <!-- Announcements can be dynamically fetched from the database -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
