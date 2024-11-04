<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("db.php");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_POST['save_excel_data'])) {
    $fileName = $_FILES['import_file']['name'];
    $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowed_ext = ['xls', 'csv', 'xlsx'];

    if (in_array($file_ext, $allowed_ext)) {
        $inputFileNamePath = $_FILES['import_file']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileNamePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        $errorMessages = [];
        $duplicateEntries = [];
        $hasCriticalError = false;

        // Display the data before importing (for review)
        echo "<h2>Preview Data to be Imported:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Name</th><th>User ID</th><th>Year</th><th>Course</th><th>Section</th><th>Email</th><th>Role</th><th>Card UID</th></tr>";

        foreach ($data as $index => $row) {
            if ($index == 0) continue; // Skip the header row

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue; // Skip this row if all columns are empty
            }

            $name = trim($row[0]);
            $user_id = trim($row[1]);
            $year = trim($row[2]); // Changed from year_section to year
            $course = trim($row[3]); // Added course
            $section = trim($row[4]); // Added section
            $email = trim($row[5]);
            $password = !empty(trim($row[6])) ? password_hash(trim($row[6]), PASSWORD_BCRYPT) : null; // Encrypt password if not empty
            $role = trim($row[7]);
            $cards_uid = trim($row[8]);

            // Display each row in the preview table
            echo "<tr><td>$name</td><td>$user_id</td><td>$year</td><td>$course</td><td>$section</td><td>$email</td><td>$role</td><td>$cards_uid</td></tr>";

            // Check if the required fields are not empty
            if (empty($user_id) || empty($email)) {
                // Skip this row due to missing required fields (but do not consider it a critical error)
                continue;
            }

            // Check if the user already exists
            $checkQuery = "SELECT * FROM users WHERE user_id = '$user_id' OR email = '$email'";
            $checkResult = mysqli_query($conn, $checkQuery);

            if (mysqli_num_rows($checkResult) == 0) {
                // Handle the case where Faculty role doesn't need year, course, and section
                if ($role === 'Faculty') {
                    $year = null;  // Set year to null for Faculty
                    $course = null;  // Set course to null for Faculty
                    $section = null;  // Set section to null for Faculty
                } elseif ($role === 'Student') {
                    // Ensure year, course, and section are required for students
                    if (empty($year) || empty($course) || empty($section)) {
                        // Skip this row if year, course, or section is missing for students
                        $errorMessages[] = "Row $index skipped: Year, Course, or Section is missing for Student.";
                        continue;
                    }
                }

                // Construct the SQL query, handling optional fields correctly
                $userQuery = "INSERT INTO users (name, user_id, year, course, section, email, password, role, cards_uid) 
                              VALUES ('$name', '$user_id', " . 
                              ($year ? "'$year'" : "NULL") . ", " . 
                              ($course ? "'$course'" : "NULL") . ", " . 
                              ($section ? "'$section'" : "NULL") . ", '$email', " . 
                              ($password ? "'$password'" : "NULL") . ", '$role', '$cards_uid')";

                // Execute the SQL query
                $result = mysqli_query($conn, $userQuery);

                // Check if query executed successfully
                if (!$result) {
                    $hasCriticalError = true;
                }
            } else {
                // Collect duplicate entries for notification
                $duplicateEntries[] = "Row $index: User ID '$user_id' or Email '$email' already exists.";
                continue;
            }
        }

        echo "</table>";

        // Display any duplicate entries found
        if (!empty($duplicateEntries)) {
            $duplicateNames = array_unique($duplicateEntries); // Get unique names only
            $duplicateCount = count($duplicateNames);

            echo "<h3>Duplicate Entries:</h3>";
            echo "<p>Number of Duplicates: $duplicateCount</p>";
            echo "<ul>";
            foreach ($duplicateNames as $duplicateName) {
                echo "<li>$duplicateName</li>";
            }
            echo "</ul>";
        }

        // If there are no critical errors, consider the import successful
        if (!$hasCriticalError) {
            if (!empty($duplicateEntries)) {
                $duplicateCount = count($duplicateNames); // Get the count of unique duplicate names
                $_SESSION['message'] = "Successfully Imported with $duplicateCount duplicate(s) or empty rows skipped.";
            } else {
                $_SESSION['message'] = "Successfully Imported with some empty rows skipped.";
            }
            
            // Redirect with success message
            header('Location: usersList.php?import_success_message=' . urlencode($_SESSION['message']));
        } else {
            $_SESSION['message'] = "There was an issue with some entries. Please review the data and try again.";
            // Redirect with error message
            header('Location: usersList.php?import_error_message=' . urlencode($_SESSION['message']));
        }
        exit(0);
    } else {
        $_SESSION['message'] = "Invalid file format. Please upload a valid Excel file.";
        // Redirect with error message
        header('Location: usersList.php?import_error_message=' . urlencode($_SESSION['message']));
        exit(0);
    }
}
?>
