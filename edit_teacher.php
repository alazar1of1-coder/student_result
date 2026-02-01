<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $teacher_id = intval($_GET['delete']);
    
    // Check if teacher has courses
    $check_stmt = $conn->prepare("SELECT COUNT(*) as course_count FROM courses WHERE teacher_id = ?");
    $check_stmt->bind_param("i", $teacher_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['course_count'] > 0) {
        $error = "Cannot delete teacher who has assigned courses!";
    } else {
        // Get user_id first
        $get_user_stmt = $conn->prepare("SELECT user_id FROM teachers WHERE id = ?");
        $get_user_stmt->bind_param("i", $teacher_id);
        $get_user_stmt->execute();
        $teacher = $get_user_stmt->get_result()->fetch_assoc();
        
        if ($teacher) {
            // Delete teacher (cascades to user via foreign key)
            $delete_stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
            $delete_stmt->bind_param("i", $teacher_id);
            
            if ($delete_stmt->execute()) {
                $success = "Teacher deleted successfully!";
            }
        }
    }
}

// Get all teachers
$teachers = $conn->query("
    SELECT t.id as teacher_id, t.department, t.qualifications,
           u.id as user_id, u.username, u.full_name, u.email, u.created_at,
           (SELECT COUNT(*) FROM courses c WHERE c.teacher_id = t.id) as course_count
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE u.role = 'teacher'
    ORDER BY u.full_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .nav-menu {
            background: #2c3e50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .nav-menu a:hover {
            background: #34495e;
        }
        
        .btn-add {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        
        .btn-add:hover {
            background: #219653;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .nav-menu a {
                display: block;
                margin: 5px 0;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Teachers</h1>
        
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_teacher.php">Add Teacher</a>
            <a href="manage_students.php">Manage Students</a>
            <a href="add_course.php">Add Course</a>
            <a href="../logout.php">Logout</a>
        </div>
        
        <div class="header-actions">
            <h2>All Teachers (<?php echo $teachers->num_rows; ?>)</h2>
            <a href="add_teacher.php" class="btn-add">+ Add New Teacher</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($teachers->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Courses</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $teacher['teacher_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($teacher['full_name']); ?></strong>
                                <?php if ($teacher['qualifications']): ?>
                                    <br><small><?php echo htmlspecialchars($teacher['qualifications']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                            <td>
                                <span class="badge <?php echo $teacher['course_count'] > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $teacher['course_count']; ?> courses
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($teacher['created_at'])); ?></td>
                            <td class="actions">
                                <a href="edit_teacher.php?id=<?php echo $teacher['teacher_id']; ?>" class="btn-edit">Edit</a>
                                <a href="?delete=<?php echo $teacher['teacher_id']; ?>" 
                                   class="btn-delete"
                                   onclick="return confirm('Delete teacher <?php echo htmlspecialchars($teacher['full_name']); ?>?')">
                                   Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <h3>No Teachers Found</h3>
                <p>Add your first teacher to get started.</p>
                <a href="add_teacher.php" class="btn-add" style="margin-top: 15px;">Add Teacher</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>