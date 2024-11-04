<?php
include("db.php");

if (isset($_POST['course']) && isset($_POST['year']) && isset($_POST['section'])) {
    $course = $_POST['course'];
    $year = $_POST['year'];
    $section = $_POST['section'];

    // Query to fetch schedules based on the selected course, year, and section
    $sql = "SELECT s.id, s.day_of_week, s.start_time, s.end_time, sub.subject_name, sec.course, sec.year, sec.section
            FROM schedules s
            JOIN subject sub ON s.subject_id = sub.subject_id
            JOIN section sec ON s.section_id = sec.section_id
            WHERE sec.course = ? AND sec.year = ? AND sec.section = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $course, $year, $section);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if we have schedules, and return the HTML structure
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $start_time_12hr = date("g:i A", strtotime($row["start_time"]));
            $end_time_12hr = date("g:i A", strtotime($row["end_time"]));

            // Display schedule details including year, course, section, and subject name
            echo '<label for="schedule_' . $row["id"] . '" class="select_label d-block mb-2">
                    <input type="checkbox" class="me-2" value="' . $row["id"] . '" name="userSchedule[]" id="schedule_' . $row["id"] . '" /> 
                    ' . $row["day_of_week"] . ' ' . $start_time_12hr . ' - ' . $end_time_12hr . 
                    ' (Subject: ' . $row["subject_name"] . ', Course: ' . $row["course"] . ', Year: ' . $row["year"] . ', Section: ' . $row["section"] . ')
                  </label>';
        }
    } else {
        echo '<label>No schedules available for the selected filters.</label>';
    }
} else {
    echo '<label>Invalid selection.</label>';
}
?>
