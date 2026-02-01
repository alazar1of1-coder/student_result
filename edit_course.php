<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

// Get course ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: add_course.php");
    exit();
}

$course_id = intval($_GET['id']);

// Get course details
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as teacher_name 
    FROM courses c
    LEFT JOIN teachers t ON c.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: add_course.php?error=course_not_found");
    exit();
}

// Get all teachers
$teachers_result = $conn->query("
    SELECT t.id, u.full_name 
    FROM teachers t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY u.full_name
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = sanitizeInput($_POST['course_code']);
    $course_name = sanitizeInput($_POST['course_name']);
    $credit_hours = $_POST['credit_hours'];
    $teacher_id = $_POST['teacher_id'];
    
    // Check if course code already exists (excluding current course)
    $check_stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
    $check_stmt->bind_param("si", $course_code, $course_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = "Course code already exists!";
    } else {
        // Update course
        $stmt = $conn->prepare("
            UPDATE courses 
            SET course_code = ?, course_name = ?, credit_hours = ?, teacher_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssiii", $course_code, $course_name, $credit_hours, $teacher_id, $course_id);
        
        if ($stmt->execute()) {
            $success = "Course updated successfully!";
            // Refresh course data
            $course['course_code'] = $course_code;
            $course['course_name'] = $course_name;
            $course['credit_hours'] = $credit_hours;
            $course['teacher_id'] = $teacher_id;
        } else {
            $error = "Error updating course: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <link rel="stylesheet" href="stayle.css">
</head>
<body>
    <div class="container">
        <h2>Edit Course: <?php echo htmlspecialchars($course['course_code']); ?></h2>
        <a href="add_course.php">← Back to Courses</a>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Course Code:</label>
                <input type="text" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Course Name:</label>
                <input type="text" name="course_name" value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Credit Hours:</label>
                <select name="credit_hours" required>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($course['credit_hours'] == $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?> Credit<?php echo $i > 1 ? 's' : ''; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Assigned Teacher:</label>
                <select name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                        <option value="<?php echo $teacher['id']; ?>" 
                            <?php echo ($course['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit">Update Course</button>
            <a href="add_course.php" class="btn-back">Cancel</a>
        </form>
    </div>
</body>
</html>