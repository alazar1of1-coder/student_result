<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

// Get all teachers for dropdown
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
    
    // Check if course code already exists
    $check_stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
    $check_stmt->bind_param("s", $course_code);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = "Course code already exists!";
    } else {
        // Insert new course
        $stmt = $conn->prepare("
            INSERT INTO courses (course_code, course_name, credit_hours, teacher_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssii", $course_code, $course_name, $credit_hours, $teacher_id);
        
        if ($stmt->execute()) {
            $success = "Course added successfully!";
            // Clear form fields
            $_POST = array();
        } else {
            $error = "Error adding course: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course - Admin</title>
    <link rel="stylesheet" href="stayle.css">
    <style>
        body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f6f9;
      margin: 0;
      padding: 0;
      color: #333;
    }

    /* Header */
    header {
      background: #2c3e50;
      color: #fff;
      padding: 15px 30px;
      text-align: center;
      font-size: 1.5rem;
      font-weight: bold;
    }

    /* Form Container */
    .form-container {
      max-width: 600px;
      margin: 30px auto;
      background: #fff;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .form-container h2 {
      margin-bottom: 20px;
      color: #34495e;
    }

    /* Input Fields */
    .form-container input,
    .form-container select,
    .form-container textarea {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
    }

    /* Buttons */
    .form-container button {
      background: #3498db;
      color: #fff;
      border: none;
      padding: 10px 15px;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .form-container button:hover {
      background: #2980b9;
    }
        .course-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Add New Course</h1>
            <nav>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="add_teacher.php">Add Teacher</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <div class="course-form">
                <?php if (isset($success)): ?>
                    <div class="success">
                        <?php echo $success; ?>
                        <br>
                        <a href="add_course.php">Add Another Course</a> | 
                        <a href="dashboard.php">Back to Dashboard</a>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="course_code">Course Code *</label>
                            <input type="text" id="course_code" name="course_code" 
                                   value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ''; ?>" 
                                   required pattern="[A-Z]{2,4}\d{3}" 
                                   title="Format: ABC123 (2-4 letters followed by 3 digits)">
                            <small>Example: CS101, MATH201, ENG301</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="credit_hours">Credit Hours *</label>
                            <select id="credit_hours" name="credit_hours" required>
                                <option value="">Select Hours</option>
                                <option value="1" <?php echo (isset($_POST['credit_hours']) && $_POST['credit_hours'] == 1) ? 'selected' : ''; ?>>1 Credit</option>
                                <option value="2" <?php echo (isset($_POST['credit_hours']) && $_POST['credit_hours'] == 2) ? 'selected' : ''; ?>>2 Credits</option>
                                <option value="3" <?php echo (isset($_POST['credit_hours']) && $_POST['credit_hours'] == 3) ? 'selected' : ''; ?>>3 Credits</option>
                                <option value="4" <?php echo (isset($_POST['credit_hours']) && $_POST['credit_hours'] == 4) ? 'selected' : ''; ?>>4 Credits</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_name">Course Name *</label>
                        <input type="text" id="course_name" name="course_name" 
                               value="<?php echo isset($_POST['course_name']) ? htmlspecialchars($_POST['course_name']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="teacher_id">Assigned Teacher *</label>
                        <select id="teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                                <option value="<?php echo $teacher['id']; ?>" 
                                    <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit">Add Course</button>
                        <a href="admin_dashboard.php" class="btn-back">Cancel</a>
                    </div>
                </form>
            </div>
            
            <!-- Display existing courses -->
            <div class="existing-courses" style="margin-top: 50px;">
                <h2>Existing Courses</h2>
                
                <?php
                // Query to get all courses with teacher names
                $courses_query = "
                    SELECT c.id, c.course_code, c.course_name, c.credit_hours, 
                           u.full_name as teacher_name, c.created_at
                    FROM courses c
                    LEFT JOIN teachers t ON c.teacher_id = t.id
                    LEFT JOIN users u ON t.user_id = u.id
                    ORDER BY c.course_code
                ";
                
                $courses_result = $conn->query($courses_query);
                
                if ($courses_result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Teacher</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($course = $courses_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo $course['credit_hours']; ?></td>
                                    <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($course['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="action-btn small">Edit</a>
                                        <a href="delete_course.php?id=<?php echo $course['id']; ?>" 
                                           class="action-btn small delete"
                                           onclick="return confirm('Delete this course?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No courses found. Add your first course above.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Auto-format course code to uppercase
        document.getElementById('course_code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
        
        // Auto-suggest course name based on code
        document.getElementById('course_code').addEventListener('blur', function(e) {
            var courseCode = this.value;
            var courseNameField = document.getElementById('course_name');
            
            // Only auto-suggest if course name is empty
            if (courseNameField.value === '' && courseCode.length >= 5) {
                var department = courseCode.substring(0, 2);
                var suggestions = {
                    'CS': 'Computer Science',
                    'MA': 'Mathematics',
                    'EN': 'English',
                    'PH': 'Physics',
                    'CH': 'Chemistry',
                    'BI': 'Biology',
                    'HI': 'History',
                    'EC': 'Economics'
                };
                
                var deptName = suggestions[department] || department;
                var courseNum = courseCode.substring(2);
                
                // Common course names based on number
                var courseNames = {
                    '101': 'Introduction to ' + deptName,
                    '201': deptName + ' II',
                    '301': 'Advanced ' + deptName,
                    '401': deptName + ' Seminar'
                };
                
                var suggestedName = courseNames[courseNum] || deptName + ' Course ' + courseNum;
                courseNameField.value = suggestedName;
            }
        });
    </script>
</body>
</html>