<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

$success = '';
$error = '';

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
            } else {
                $error = "Error deleting teacher: " . $conn->error;
            }
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? trim($_GET['department']) : '';

// Build query
$query = "
    SELECT t.id as teacher_id, t.department, t.qualifications,
           u.id as user_id, u.username, u.full_name, u.email, u.created_at,
           (SELECT COUNT(*) FROM courses c WHERE c.teacher_id = t.id) as course_count,
           (SELECT COUNT(*) FROM results r 
            JOIN courses c ON r.course_id = c.id 
            WHERE c.teacher_id = t.id) as result_count
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE u.role = 'teacher'
";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR t.department LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

if ($department_filter) {
    $query .= " AND t.department = ?";
    $params[] = $department_filter;
    $types .= 's';
}

$query .= " ORDER BY u.full_name";

// Get all departments for filter
$departments_result = $conn->query("
    SELECT DISTINCT department 
    FROM teachers 
    WHERE department IS NOT NULL AND department != '' 
    ORDER BY department
");

// Prepare and execute main query
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$teachers = $stmt->get_result();

// Count total teachers
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 25px 30px;
        }
        
        header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .nav-menu {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        main {
            padding: 30px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e9ecef;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .action-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .action-header h2 {
            color: #2c3e50;
            font-size: 24px;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .filters {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .filters h3 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            align-items: end;
        }
        
        @media (max-width: 992px) {
            .filter-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .btn-filter {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-clear {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-clear:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        thead {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }
        
        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: all 0.3s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        td {
            padding: 16px 15px;
            color: #495057;
        }
        
        .teacher-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .teacher-qualifications {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 70px;
        }
        
        .badge-courses {
            background: #e8f4fc;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .badge-results {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .badge-department {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.3);
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-data h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #495057;
        }
        
        .no-data p {
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 10px;
        }
        
        .page-link {
            padding: 10px 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .page-link.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .export-options {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .container {
                border-radius: 10px;
            }
            
            header {
                padding: 20px;
            }
            
            main {
                padding: 20px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-card .number {
                font-size: 28px;
            }
            
            .action-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-add {
                justify-content: center;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
                text-align: center;
            }
            
            .filter-buttons {
                display: flex;
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .nav-menu a {
                text-align: center;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
            
            .btn-filter,
            .btn-clear {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Teachers</h1>
            <div class="nav-menu">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="add_teacher.php">➕ Add Teacher</a>
                <a href="manage_students.php">👥 Manage Students</a>
                <a href="add_course.php">📚 Manage Courses</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </header>
        
        <main>
            <!-- Statistics Summary -->
            <div class="stats-summary">
                <div class="stat-card">
                    <h3>Total Teachers</h3>
                    <div class="number"><?php echo $total_teachers; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Showing</h3>
                    <div class="number"><?php echo $teachers->num_rows; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Departments</h3>
                    <div class="number"><?php echo $departments_result->num_rows; ?></div>
                </div>
            </div>
            
            <!-- Action Header -->
            <div class="action-header">
                <h2>All Teachers</h2>
                <a href="add_teacher.php" class="btn-add">
                    <span>➕</span> Add New Teacher
                </a>
            </div>
            
            <!-- Messages -->
            <?php if ($success): ?>
                <div class="message success">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Search & Filters -->
            <div class="filters">
                <h3>🔍 Search & Filter Teachers</h3>
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Search Teachers:</label>
                            <input type="text" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name, username, or department">
                        </div>
                        
                        <div class="filter-group">
                            <label>Filter by Department:</label>
                            <select name="department">
                                <option value="">All Departments</option>
                                <?php 
                                $departments_result->data_seek(0); // Reset pointer
                                while ($dept = $departments_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>"
                                        <?php echo ($department_filter == $dept['department']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group filter-buttons">
                            <button type="submit" class="btn-filter">🔍 Apply Filters</button>
                            <a href="manage_teachers.php" class="btn-clear">🗑️ Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Teachers Table -->
            <?php if ($teachers->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Teacher Details</th>
                                <th>Contact</th>
                                <th>Department</th>
                                <th>Statistics</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $teacher['teacher_id']; ?></strong>
                                    </td>
                                    <td>
                                        <div class="teacher-name">
                                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                                        </div>
                                        <div style="color: #666; font-size: 14px; margin-top: 5px;">
                                            @<?php echo htmlspecialchars($teacher['username']); ?>
                                        </div>
                                        <?php if ($teacher['qualifications']): ?>
                                            <div class="teacher-qualifications">
                                                📚 <?php echo htmlspecialchars($teacher['qualifications']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>" 
                                           style="color: #3498db; text-decoration: none;">
                                           ✉️ <?php echo htmlspecialchars($teacher['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-department">
                                            <?php echo htmlspecialchars($teacher['department']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <span class="badge badge-courses" title="Courses Assigned">
                                                📚 <?php echo $teacher['course_count']; ?> courses
                                            </span>
                                            <span class="badge badge-results" title="Results Added">
                                                📝 <?php echo $teacher['result_count']; ?> results
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($teacher['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit_teacher.php?id=<?php echo $teacher['teacher_id']; ?>" 
                                               class="btn-action btn-edit">
                                               ✏️ Edit
                                            </a>
                                            <a href="delete_teacher.php?id=<?php echo $teacher['teacher_id']; ?>" 
                                               class="btn-action btn-delete"
                                               onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($teacher['full_name']); ?>?')">
                                               🗑️ Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Export Options -->
                <div class="export-options">
                    <a href="export_teachers.php?<?php echo http_build_query($_GET); ?>" 
                       class="btn-action btn-edit" style="background: #27ae60;">
                       📥 Export to CSV
                    </a>
                    <a href="print_teachers.php?<?php echo http_build_query($_GET); ?>" 
                       class="btn-action btn-edit" target="_blank" style="background: #9b59b6;">
                       🖨️ Print List
                    </a>
                </div>
                
            <?php else: ?>
                <div class="no-data">
                    <h3>No Teachers Found</h3>
                    <p><?php echo $search || $department_filter ? 'Try changing your search filters.' : 'Add your first teacher to get started.'; ?></p>
                    <a href="add_teacher.php" class="btn-add" style="display: inline-flex;">
                        <span>➕</span> Add New Teacher
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        // Confirm before delete
        document.addEventListener('DOMContentLoaded', function() {
            var deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this teacher?\n\nThis action cannot be undone!')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Auto-focus search field
            var searchField = document.querySelector('input[name="search"]');
            if (searchField && searchField.value === '') {
                searchField.focus();
            }
            
            // Add animation to table rows
            var tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(function(row, index) {
                row.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>