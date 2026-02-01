<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student details
$stmt = $conn->prepare("SELECT s.* FROM students s WHERE s.user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="stayle.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, <?php echo $_SESSION['full_name']; ?></h1>
            <nav>
                <a href="view_result.php">View Results</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <div class="student-info">
                <h2>Student Information</h2>
                <p><strong>Roll Number:</strong> <?php echo $student['roll_number']; ?></p>
                <p><strong>Class:</strong> <?php echo $student['class']; ?></p>
            </div>
            
            <div class="quick-actions">
                <a href="view_result.php" class="action-btn">View Results</a>
                <a href="download_pdf.php" class="action-btn">Download Results (PDF)</a>
            </div>
        </main>
    </div>
</body>
</html>