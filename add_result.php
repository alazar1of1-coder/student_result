<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Get teacher's courses
$stmt = $conn->prepare("
    SELECT c.id, c.course_code, c.course_name 
    FROM courses c 
    WHERE c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_roll = sanitizeInput($_POST['student_roll']);
    $course_id = $_POST['course_id'];
    $marks_obtained = $_POST['marks_obtained'];
    $grade = getGrade($marks_obtained);
    
    // Get student ID
    $stmt = $conn->prepare("SELECT id FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $student_roll);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $student = $result->fetch_assoc();
        $student_id = $student['id'];
        
        // Get teacher ID from teachers table
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $teacher = $stmt->get_result()->fetch_assoc();
        
        // Insert result
        $stmt = $conn->prepare("
            INSERT INTO results (student_id, course_id, marks_obtained, grade, teacher_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iidsi", $student_id, $course_id, $marks_obtained, $grade, $teacher['id']);
        
        if ($stmt->execute()) {
            $success = "Result added successfully!";
        } else {
            $error = "Error adding result: " . $conn->error;
        }
    } else {
        $error = "Student not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Result</title>
    <link rel="stylesheet" href="stayle.css">
</head>
<body>
    <div class="container">
        <h2>Add Student Result</h2>
        <a href="logout.php">logout</a>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Student Roll Number:</label>
                <input type="text" name="student_roll" required>
            </div>
            
            <div class="form-group">
                <label>Select Course:</label>
                <select name="course_id" required>
                    <option value="">Select Course</option>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Marks Obtained (out of 100):</label>
                <input type="number" name="marks_obtained" min="0" max="100" step="0.01" required>
            </div>
            
            <button type="submit">Add Result</button>
        </form>
    </div>
</body>
</html>