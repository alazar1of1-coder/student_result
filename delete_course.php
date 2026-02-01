<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: add_course.php");
    exit();
}

$course_id = intval($_GET['id']);

// Check if course has any results
$check_stmt = $conn->prepare("SELECT COUNT(*) as result_count FROM results WHERE course_id = ?");
$check_stmt->bind_param("i", $course_id);
$check_stmt->execute();
$result = $check_stmt->get_result()->fetch_assoc();

if ($result['result_count'] > 0) {
    header("Location: add_course.php?error=cannot_delete_has_results");
    exit();
}

// Delete the course
$stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);

if ($stmt->execute()) {
    header("Location: add_course.php?success=course_deleted");
} else {
    header("Location: add_course.php?error=delete_failed");
}
exit();
?>