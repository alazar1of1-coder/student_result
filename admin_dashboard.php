<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="add_teacher.php">Add Teacher</a>
                <a href="add_course.php">Add Course</a>
                <a href="add_student.php">Add Student</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <div class="stats">
                <?php
                $total_teachers = $conn->query("SELECT COUNT(*) FROM teachers")->fetch_row()[0];
                $total_students = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
                $total_courses = $conn->query("SELECT COUNT(*) FROM courses")->fetch_row()[0];
                ?>
                
                <div class="stat-card teachers">
    <h3>Total Teachers</h3>
    <p><?php echo $total_teachers; ?></p>
</div>

<div class="stat-card students">
    <h3>Total Students</h3>
    <p><?php echo $total_students; ?></p>
</div>

<div class="stat-card courses">
    <h3>Total Courses</h3>
    <p><?php echo $total_courses; ?></p>
</div>

            </div>
        </main>
    </div>
</body>
</html>