<?php
require_once 'db.php';
require_once 'functions.php';
redirectIfNotLoggedIn();
checkRole(['admin']);

// Handle student deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $student_id = intval($_GET['delete']);
    
    // Check if student has results
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as result_count 
        FROM results r 
        JOIN students s ON r.student_id = s.id 
        WHERE s.id = ?
    ");
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['result_count'] > 0) {
        $error = "Cannot delete student who has results! Delete results first.";
    } else {
        // Get user_id first
        $get_user_stmt = $conn->prepare("SELECT user_id FROM students WHERE id = ?");
        $get_user_stmt->bind_param("i", $student_id);
        $get_user_stmt->execute();
        $student = $get_user_stmt->get_result()->fetch_assoc();
        
        if ($student) {
            // Delete student (cascades to user via foreign key)
            $delete_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $delete_stmt->bind_param("i", $student_id);
            
            if ($delete_stmt->execute()) {
                $success = "Student deleted successfully!";
            }
        }
    }
}

// Search and filter functionality
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? sanitizeInput($_GET['class']) : '';

// Build query with filters
$query = "
    SELECT s.id, s.roll_number, s.class, s.date_of_birth,
           u.full_name, u.username, u.email, u.created_at,
           COUNT(r.id) as result_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN results r ON s.id = r.student_id
    WHERE u.role = 'student'
";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (u.full_name LIKE ? OR s.roll_number LIKE ? OR u.username LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

if ($class_filter) {
    $query .= " AND s.class = ?";
    $params[] = $class_filter;
    $types .= 's';
}

$query .= " GROUP BY s.id ORDER BY s.roll_number";

// Get all distinct classes for filter dropdown
$classes_result = $conn->query("
    SELECT DISTINCT class 
    FROM students 
    WHERE class IS NOT NULL AND class != '' 
    ORDER BY class
");

// Prepare and execute main query
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Students - Admin</title>
    <style>
/* ===== Reset & Base ===== */
* {
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    background: #f4f6f9;
    margin: 0;
    padding: 0;
}

/* ===== Layout ===== */
.container {
    max-width: 1200px;
    margin: 30px auto;
    background: #ffffff;
    padding: 20px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* ===== Header ===== */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #eee;
    padding-bottom: 15px;
    margin-bottom: 25px;
}

header h1 {
    margin: 0;
    color: #333;
}

nav a {
    text-decoration: none;
    margin-left: 10px;
    padding: 8px 14px;
    background: #0d6efd;
    color: #fff;
    border-radius: 5px;
    font-size: 14px;
}

nav a:hover {
    background: #084298;
}

/* ===== Messages ===== */
.success {
    background: #d1e7dd;
    color: #0f5132;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.error {
    background: #f8d7da;
    color: #842029;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
}

/* ===== Filters ===== */
.filters {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 25px;
}

.filter-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    font-weight: bold;
    display: block;
    margin-bottom: 6px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.filter-group button,
.btn-back {
    padding: 9px 14px;
    border: none;
    border-radius: 5px;
    background: #198754;
    color: white;
    cursor: pointer;
    text-decoration: none;
}

.btn-back {
    background: #6c757d;
}

.filter-group button:hover {
    background: #146c43;
}

/* ===== Table ===== */
table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #0d6efd;
    color: #fff;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

tbody tr:hover {
    background: #f1f1f1;
}

/* ===== Status ===== */
.student-status {
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: bold;
}

.status-active {
    background: #d1e7dd;
    color: #0f5132;
}

.status-no-results {
    background: #fff3cd;
    color: #664d03;
}

/* ===== Buttons ===== */
.action-btn {
    background: #0d6efd;
    color: white;
    padding: 8px 14px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
}

.action-btn:hover {
    background: #084298;
}

.action-btn.small {
    padding: 6px 10px;
    font-size: 13px;
}

.action-btn.delete {
    background: #dc3545;
}

.action-btn.delete:hover {
    background: #bb2d3b;
}

.view-results {
    background: #198754;
}

.view-results:hover {
    background: #146c43;
}

.student-actions {
    display: flex;
    gap: 6px;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .student-actions {
        flex-direction: column;
    }
}
</style>

 
    
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Students</h1>
            <nav>
                
                <a href="add_student.php">Add Student</a>
                <a href="add_teacher.php">Add Teacher</a>
                <a href="add_course.php">Add Course</a>
                <a href="admin_dashboard.php">Back
                </a>
            </nav>
        </header>
        
        <main>
            <!-- Messages -->
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filters">
                <h3>Search & Filter</h3>
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Search (Name/Roll/Username):</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search students...">
                        </div>
                        
                        <div class="filter-group">
                            <label>Filter by Class:</label>
                            <select name="class">
                                <option value="">All Classes</option>
                                <?php while ($class = $classes_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($class['class']); ?>"
                                        <?php echo ($class_filter == $class['class']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit">Apply Filters</button>
                            <a href="manage_students.php" class="btn-back">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Students List -->
            <div class="students-list">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>All Students (<?php echo $students->num_rows; ?>)</h2>
                    <a href="add_student.php" class="action-btn">+ Add New Student</a>
                </div>
                
                <?php if ($students->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Roll No.</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Results</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): 
                                $age = date_diff(date_create($student['date_of_birth']), date_create('today'))->y;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($student['full_name']); ?>
                                        <br><small>Age: <?php echo $age; ?> years</small>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>">
                                            <?php echo htmlspecialchars($student['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="student-status 
                                            <?php echo $student['result_count'] > 0 ? 'status-active' : 'status-no-results'; ?>">
                                            <?php echo $student['result_count']; ?> results
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($student['created_at'])); ?></td>
                                    <td>
                                        <div class="student-actions">
                                            <a href="edit_course.php?id=<?php echo $student['id']; ?>" 
                                               class="action-btn small">Edit</a>
                                            <a href="view_student_results.php?student_id=<?php echo $student['id']; ?>" 
                                               class="action-btn small view-results">Results</a>
                                            <a href="?delete=<?php echo $student['id']; ?>" 
                                               class="action-btn small delete"
                                               onclick="return confirm('Delete student <?php echo htmlspecialchars($student['full_name']); ?>? This action cannot be undone!')">
                                               Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <!-- Export Options -->
                    <div style="margin-top: 30px; text-align: center;">
                        <a href="export_students.php?<?php echo http_build_query($_GET); ?>" 
                           class="action-btn">Export to CSV</a>
                        <a href="print_students.php?<?php echo http_build_query($_GET); ?>" 
                           class="action-btn" target="_blank">Print List</a>
                    </div>
                    
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3>No students found</h3>
                        <p><?php echo $search ? 'Try a different search term.' : 'Add your first student.'; ?></p>
                        <a href="add_student.php" class="action-btn">Add Student</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>