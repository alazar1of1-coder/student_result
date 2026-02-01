<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function checkRole($allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: index.php?error=unauthorized");
        exit();
    }
}

function getGrade($marks) {
    if ($marks >= 85) return 'A+';
    if ($marks >= 80) return 'A';
    if ($marks >= 75) return 'B+';
    if ($marks >= 70) return 'B';
    if ($marks >= 65) return 'C+';
    if ($marks >= 60) return 'C';
    if ($marks >= 55) return 'D+';
    if ($marks >= 50) return 'D';
    return 'F';
}

function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}
?>