<?php
require('./fpdf186/fpdf.php');
include('db.php');
session_start();

// Check if the user is logged in and is either an Admin or Faculty member
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Faculty')) {
    header('Location: login.php');
    exit();
}

// Get the selected schedule and date from the form submission
$scheduleId = isset($_POST['schedule_id']) ? $_POST['schedule_id'] : null;
$attendanceDate = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');

// Query to fetch schedule details (subject and section)
$scheduleQuery = "
    SELECT 
        s.start_time, s.end_time, s.day_of_week, 
        su.subject_name, se.section_name 
    FROM schedules s
    INNER JOIN subject su ON s.subject_id = su.subject_id
    INNER JOIN section se ON s.section_id = se.section_id
    WHERE s.id = ?
";
$scheduleStmt = $conn->prepare($scheduleQuery);
$scheduleStmt->bind_param("i", $scheduleId);
$scheduleStmt->execute();
$scheduleResult = $scheduleStmt->get_result();
$scheduleInfo = $scheduleResult->fetch_assoc();

// Query to fetch attendance data for the selected schedule and date
$attendanceQuery = "
    SELECT 
        u.name,
        u.id AS user_id,
        u.user_id AS student_id,
        CONCAT(u.year, '-', u.course, '-', u.section) AS year_course_section,
        a.date,
        a.time_in,
        a.time_out,
        CASE 
            WHEN a.id IS NOT NULL THEN 'Present'
            ELSE 'Absent'
        END AS attendance_status
    FROM 
        user_schedules us
    JOIN users u ON us.users_id = u.id
    LEFT JOIN attendance a ON us.schedules_id = a.schedules_id 
        AND a.users_id = u.id  -- Match the user with attendance record
        AND a.date = ?  -- Match the selected date
    WHERE 
        us.schedules_id = ?
";
$stmt = $conn->prepare($attendanceQuery);
$stmt->bind_param("si", $attendanceDate, $scheduleId);
$stmt->execute();
$result = $stmt->get_result();

// Initialize the FPDF library
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);

// Add a title to the PDF
$pdf->Cell(190, 10, 'Attendance Report', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);

// Add schedule details (subject, section, and date) with date aligned to the right
$pdf->Ln(5);
$pdf->Cell(50, 10, 'Subject: ' . $scheduleInfo['subject_name'], 0, 0, 'L');
$pdf->Cell(0, 10, 'Date: ' . date('F d, Y', strtotime($attendanceDate)), 0, 1, 'R');
$pdf->Cell(50, 10, 'Section: ' . $scheduleInfo['section_name'], 0, 1, 'L');
$pdf->Cell(50, 10, 'Day: ' . $scheduleInfo['day_of_week'] . ' (' . $scheduleInfo['start_time'] . ' - ' . $scheduleInfo['end_time'] . ')', 0, 1, 'L');
$pdf->Ln(5);

// Set up the table header with fixed cell widths and bold, centered text
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 10, 'Name', 1, 0, 'C');
$pdf->Cell(30, 10, 'Student ID', 1, 0, 'C');
$pdf->Cell(50, 10, 'Year-Course-Section', 1, 0, 'C');
$pdf->Cell(25, 10, 'Time In', 1, 0, 'C');
$pdf->Cell(25, 10, 'Time Out', 1, 0, 'C');
$pdf->Cell(20, 10, 'Status', 1, 1, 'C');

// Fetch and write each row of the attendance data with smaller text
$pdf->SetFont('Arial', '', 8);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(40, 8, $row['name'], 1);
    $pdf->Cell(30, 8, $row['student_id'], 1);
    $pdf->Cell(50, 8, $row['year_course_section'], 1);
    $pdf->Cell(25, 8, ($row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : ''), 1);
    $pdf->Cell(25, 8, ($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : ''), 1);
    $pdf->Cell(20, 8, $row['attendance_status'], 1, 1, 'C');
}

// Prepare the filename with subject and section
$filename = 'Attendance_Report_' . $scheduleInfo['subject_name'] . '_' . $scheduleInfo['section_name'] . '_' . $attendanceDate . '.pdf';
$filename = str_replace(' ', '_', $filename); // Replace spaces with underscores for file compatibility

// Output the PDF as a downloadable file with the proper filename
$pdf->Output('D', $filename);

?>
