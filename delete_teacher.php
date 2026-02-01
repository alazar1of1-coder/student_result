<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

// Check if teacher ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_teachers.php?error=invalid_id");
    exit();
}

$teacher_id = intval($_GET['id']);

// Get teacher details for confirmation
$stmt = $conn->prepare("
    SELECT u.full_name, u.username, 
           (SELECT COUNT(*) FROM courses WHERE teacher_id = t.id) as course_count
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_teachers.php?error=teacher_not_found");
    exit();
}

$teacher = $result->fetch_assoc();

$error = '';
$success = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
        // Check if teacher has courses
        if ($teacher['course_count'] > 0) {
            // Option 1: Don't delete and redirect
            header("Location: manage_teachers.php?error=cannot_delete_has_courses");
            exit();
            
            // Option 2: Transfer courses to another teacher (uncomment if needed)
            /*
            if (isset($_POST['transfer_to']) && is_numeric($_POST['transfer_to'])) {
                $new_teacher_id = intval($_POST['transfer_to']);
                
                $conn->begin_transaction();
                try {
                    // Transfer courses
                    $transfer_stmt = $conn->prepare("UPDATE courses SET teacher_id = ? WHERE teacher_id = ?");
                    $transfer_stmt->bind_param("ii", $new_teacher_id, $teacher_id);
                    $transfer_stmt->execute();
                    
                    // Now delete the teacher
                    $delete_stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
                    $delete_stmt->bind_param("i", $teacher_id);
                    $delete_stmt->execute();
                    
                    $conn->commit();
                    $success = "Teacher deleted successfully! Courses transferred to another teacher.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error deleting teacher: " . $e->getMessage();
                }
            } else {
                $error = "Please select a teacher to transfer courses to!";
            }
            */
        } else {
            // Teacher has no courses, safe to delete
            $conn->begin_transaction();
            try {
                // Delete the teacher (user will be deleted via cascade)
                $delete_stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
                $delete_stmt->bind_param("i", $teacher_id);
                
                if ($delete_stmt->execute()) {
                    $conn->commit();
                    $success = "Teacher deleted successfully!";
                } else {
                    throw new Exception("Failed to delete teacher");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error deleting teacher: " . $e->getMessage();
            }
        }
    } else {
        // User cancelled deletion
        header("Location: manage_teachers.php");
        exit();
    }
}

// Get other teachers for course transfer (if needed)
$other_teachers = $conn->query("
    SELECT t.id, u.full_name 
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.id != $teacher_id
    ORDER BY u.full_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Teacher - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #e74c3c;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #f39c12;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        
        .warning-box h3 {
            color: #d35400;
            margin-bottom: 10px;
        }
        
        .teacher-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        
        .teacher-details p {
            margin: 8px 0;
            color: #333;
        }
        
        .teacher-details strong {
            color: #2c3e50;
        }
        
        .message {
            padding: 15px;
            margin: 15px 0;
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
        
        .danger-count {
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
            color: #721c24;
        }
        
        .transfer-section {
            background: #e8f4fc;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #b8daff;
            display: <?php echo ($teacher['course_count'] > 0) ? 'block' : 'none'; ?>;
        }
        
        .transfer-section h4 {
            color: #004085;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .confirmation {
            text-align: center;
            margin: 25px 0;
            font-size: 18px;
            color: #e74c3c;
            font-weight: bold;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        button {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            flex: 1;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .btn-cancel {
            background: #7f8c8d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #6c7b7d;
        }
        
        .btn-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        
        .btn-back:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Delete Teacher</h1>
        
        <div class="warning-box">
            <h3>Warning: This action cannot be undone!</h3>
            <p>You are about to permanently delete a teacher from the system. All associated data will be removed.</p>
        </div>
        
        <div class="teacher-details">
            <p><strong>Teacher Name:</strong> <?php echo htmlspecialchars($teacher['full_name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($teacher['username']); ?></p>
            <p><strong>Courses Assigned:</strong> <?php echo $teacher['course_count']; ?> courses</p>
        </div>
        
        <?php if ($success): ?>
            <div class="message success">
                <?php echo $success; ?>
                <div style="margin-top: 15px;">
                    <a href="manage_teachers.php" class="btn-back">← Back to Teachers</a>
                </div>
            </div>
        <?php elseif ($error): ?>
            <div class="message error">
                <?php echo $error; ?>
                <div style="margin-top: 15px;">
                    <a href="manage_teachers.php" class="btn-back">← Back to Teachers</a>
                </div>
            </div>
        <?php else: ?>
        
            <?php if ($teacher['course_count'] > 0): ?>
                <div class="danger-count">
                    ⚠️ This teacher has <?php echo $teacher['course_count']; ?> assigned course(s)!
                </div>
                
                <!-- Optional: Uncomment if you want to implement course transfer -->
                <!--
                <div class="transfer-section" id="transferSection">
                    <h4>Transfer Courses to Another Teacher</h4>
                    <div class="form-group">
                        <label>Select teacher to transfer courses to:</label>
                        <select name="transfer_to" id="transfer_to">
                            <option value="">-- Select Teacher --</option>
                            <?php while ($other_teacher = $other_teachers->fetch_assoc()): ?>
                                <option value="<?php echo $other_teacher['id']; ?>">
                                    <?php echo htmlspecialchars($other_teacher['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                -->
                
                <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 5px; margin: 15px 0;">
                    <p style="color: #d35400; font-weight: bold;">
                        ❌ Cannot delete this teacher because they have assigned courses.<br>
                        Please reassign or delete their courses first.
                    </p>
                </div>
                
                <div class="form-actions">
                    <a href="manage_teachers.php" style="text-decoration: none;">
                        <button type="button" class="btn-cancel">Cancel</button>
                    </a>
                </div>
                
            <?php else: ?>
                <div class="confirmation">
                    Are you sure you want to delete this teacher?
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="confirm" value="yes">
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-delete">Yes, Delete Teacher</button>
                        <a href="manage_teachers.php" style="text-decoration: none;">
                            <button type="button" class="btn-cancel">No, Cancel</button>
                        </a>
                    </div>
                </form>
            <?php endif; ?>
            
            <a href="manage_teachers.php" class="btn-back">← Back to Teachers List</a>
        
        <?php endif; ?>
    </div>
    
    <script>
        // Show warning when delete button is clicked
        document.addEventListener('DOMContentLoaded', function() {
            var deleteBtn = document.querySelector('.btn-delete');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    if (!confirm('Are you absolutely sure you want to delete this teacher?\n\nThis action cannot be undone!')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
            
            // Show/hide transfer section based on checkbox
            var transferCheckbox = document.getElementById('transferCourses');
            var transferSection = document.getElementById('transferSection');
            
            if (transferCheckbox && transferSection) {
                transferCheckbox.addEventListener('change', function() {
                    transferSection.style.display = this.checked ? 'block' : 'none';
                });
            }
        });
    </script>
</body>
</html>