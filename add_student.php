<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $roll_number = trim($_POST['roll_number']);
    $class = trim($_POST['class']);
    
    // Simple validation
    if (empty($full_name) || empty($username) || empty($password) || empty($roll_number) || empty($class)) {
        $error = "All fields are required!";
    } else {
        // Check if username exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Username already taken!";
        } else {
            // Check if roll number exists
            $check = $conn->prepare("SELECT id FROM students WHERE roll_number = ?");
            $check->bind_param("s", $roll_number);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                $error = "Roll number already exists!";
            } else {
                // Simple password hashing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Start database transaction
                $conn->begin_transaction();
                
                try {
                    // Add to users table
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'student', ?)");
                    $stmt->bind_param("sss", $username, $hashed_password, $full_name);
                    $stmt->execute();
                    $user_id = $conn->insert_id;
                    
                    // Add to students table
                    $stmt = $conn->prepare("INSERT INTO students (user_id, roll_number, class) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user_id, $roll_number, $class);
                    $stmt->execute();
                    
                    $conn->commit();
                    $success = "Student added successfully!";
                    
                    // Clear form
                    $_POST = array();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .nav-menu {
            background: #4CAF50;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
            padding: 5px 10px;
        }
        
        .nav-menu a:hover {
            background: #45a049;
            border-radius: 3px;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        
        .buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-add {
            background: #4CAF50;
            color: white;
        }
        
        .btn-add:hover {
            background: #45a049;
        }
        
        .btn-reset {
            background: #ff9800;
            color: white;
        }
        
        .btn-reset:hover {
            background: #e68900;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .student-list {
            margin-top: 30px;
            border-top: 2px solid #eee;
            padding-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }
            
            .nav-menu {
                text-align: center;
            }
            
            .nav-menu a {
                display: inline-block;
                margin: 5px;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            button, .btn-back {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Student</h1>
        
        <div class="nav-menu">
            <a href="admin_dashboard.php">Back</a>
            <a href="manage_student.php">View Students</a>
            <a href="add_teacher.php">Add Teacher</a>
            <a href="admin_course.php">Add Course</a>
            <a href="admin_dashboard.php">Logout</a>
        </div>
        
        <?php if ($success): ?>
            <div class="message success">
                <?php echo $success; ?>
                <div style="margin-top: 10px;">
                    <a href="add_student.php" style="color: #155724;">Add Another Student</a> | 
                    <a href="manage_student.php" style="color: #155724;">View All Students</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                       required>
                <small style="color: #666;">Choose a unique username</small>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
                <small style="color: #666;">Enter password for student login</small>
            </div>
            
            <div class="form-group">
                <label for="roll_number">Roll Number *</label>
                <input type="text" id="roll_number" name="roll_number" 
                       value="<?php echo isset($_POST['roll_number']) ? htmlspecialchars($_POST['roll_number']) : ''; ?>" 
                       required>
                <small style="color: #666;">Example: 101, 202, S001</small>
            </div>
            
            <div class="form-group">
                <label for="class">Class *</label>
                <input type="text" id="class" name="class" 
                       value="<?php echo isset($_POST['class']) ? htmlspecialchars($_POST['class']) : ''; ?>" 
                       required>
                <small style="color: #666;">Example: 10th, 12th, CS-2024</small>
            </div>
            
            <div class="buttons">
                <button type="submit" class="btn-add">Add Student</button>
                <button type="reset" class="btn-reset">Clear Form</button>
                <a href="admin_dashboard.php" class="btn-back">Cancel</a>
            </div>
        </form>
        
        <div class="student-list">
            <h3>Recently Added Students</h3>
            
            <?php
            // Get last 5 students
            $result = $conn->query("
                SELECT s.roll_number, s.class, u.full_name, u.username, u.created_at 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                ORDER BY u.created_at DESC 
                LIMIT 5
            ");
            
            if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Roll No.</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Username</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['class']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="manage_student.php" class="btn-back">View All Students</a>
                </div>
            <?php else: ?>
                <p>No students added yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Simple password generator
        function generatePassword() {
            var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            var password = "";
            for (var i = 0; i < 8; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById("password").value = password;
            alert("Generated Password: " + password);
        }
        
        // Add password generator button
        window.onload = function() {
            var passwordField = document.querySelector('#password');
            var parent = passwordField.parentNode;
            
            var genBtn = document.createElement('button');
            genBtn.type = 'button';
            genBtn.textContent = 'Generate Password';
            genBtn.style.marginTop = '5px';
            genBtn.style.padding = '5px 10px';
            genBtn.style.background = '#28a745';
            genBtn.style.color = 'white';
            genBtn.style.border = 'none';
            genBtn.style.borderRadius = '3px';
            genBtn.style.cursor = 'pointer';
            genBtn.onclick = generatePassword;
            
            parent.appendChild(genBtn);
        };
    </script>
</body>
</html>