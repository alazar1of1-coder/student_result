<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student's results
$query = "
    SELECT 
        c.course_code,
        c.course_name,
        r.marks_obtained,
        r.total_marks,
        r.grade,
        r.submitted_at,
        u.full_name as teacher_name
    FROM results r
    JOIN courses c ON r.course_id = c.id
    JOIN teachers t ON r.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN students s ON r.student_id = s.id
    WHERE s.user_id = ?
    ORDER BY r.submitted_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Results</title>
    <link rel="stylesheet" href="stayle.css">
</head>
<body>
    <div class="container">
        <h2>Your Results</h2>
        <a href="logout.php">←logout</a>
        
        <?php if ($results->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Marks Obtained</th>
                        <th>Total Marks</th>
                        <th>Grade</th>
                        <th>Teacher</th>
                        <th>Submitted Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['course_code']; ?></td>
                            <td><?php echo $row['course_name']; ?></td>
                            <td><?php echo $row['marks_obtained']; ?></td>
                            <td><?php echo $row['total_marks']; ?></td>
                            <td class="grade-<?php echo strtolower($row['grade']); ?>">
                                <?php echo $row['grade']; ?>
                            </td>
                            <td><?php echo $row['teacher_name']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['submitted_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
    </div>
</body>
</html>