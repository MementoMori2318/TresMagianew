<?php
include("db.php"); // Include the database connection
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Delete Schedule
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure ID is an integer

    if ($id <= 0) {
        error_log("Invalid ID for deletion: " . $id);
        header("Location: schedule.php?error_message=" . urlencode("Invalid request parameters."));
        exit;
    }

    // Start a transaction to ensure all deletions occur or none at all
    $conn->begin_transaction();

    try {
        // Delete from the attendance table first
        $stmt = $conn->prepare("DELETE FROM attendance WHERE schedules_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting from attendance: " . $stmt->error);
        }
        $stmt->close();

        // Delete from the user_schedules table
        $stmt = $conn->prepare("DELETE FROM user_schedules WHERE schedules_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting from user_schedules: " . $stmt->error);
        }
        $stmt->close();

        // Delete from the schedules table
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting from schedules: " . $stmt->error);
        }
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        $success_message = "Schedule deleted successfully!";
        header("Location: schedule.php?success_message=" . urlencode($success_message));
        exit; // Ensure no further code is executed after redirection
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $conn->rollback();
        error_log("Error during deletion: " . $e->getMessage());
        header("Location: schedule.php?error_message=" . urlencode("Error deleting schedule."));
        exit; // Ensure no further code is executed after redirection
    }

    $conn->close();
} else {
    error_log("Invalid request parameters.");
    header("Location: schedule.php?error_message=" . urlencode("Invalid request parameters."));
    exit; // Ensure no further code is executed after redirection
}
?>
