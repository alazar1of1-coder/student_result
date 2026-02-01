<?php
session_start();
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin_dashboard.php");
            break;
        case 'teacher':
            header("Location: add_result.php");
            break;
        case 'student':
            header("Location: view_result.php");
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Result Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

   <style>
  /* Reset some defaults */
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
  }

  /* Body styling */
  body {
    background: linear-gradient(135deg, #4e73df, #1cc88a);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  /* Login container */
  .login-container {
    background: #fff;
    padding: 2rem;
    border-radius: 10px;
    width: 350px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    text-align: center;
  }

  /* Title */
  .login-container h2 {
    margin-bottom: 1.5rem;
    color: #333;
  }

  /* Input fields */
  .login-container input[type="text"],
  .login-container input[type="password"] {
    width: 100%;
    padding: 0.8rem;
    margin: 0.5rem 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    transition: border-color 0.3s;
  }

  .login-container input:focus {
    border-color: #4e73df;
    outline: none;
  }

  /* Button */
  .login-container button {
    width: 100%;
    padding: 0.8rem;
    margin-top: 1rem;
    background: #4e73df;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s;
  }

  .login-container button:hover {
    background: #2e59d9;
  }

  /* Demo accounts link */
  .login-container a {
    display: block;
    margin-top: 1rem; 
    color: #1cc88a;
    text-decoration: none;
  }

  .login-container a:hover {
    text-decoration: underline;
  }
</style>

</head>

<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Student Result Management System</h2>

            <?php if (isset($_GET['error'])): ?>
                <div class="error">
                    <?php
                    $errors = [
                        'invalid_credentials' => 'Invalid username or password',
                        'user_not_found' => 'User not found',
                        'unauthorized' => 'Unauthorized access'
                    ];
                    echo $errors[$_GET['error']] ?? 'Login error';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="success">Logged out successfully</div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit">Login</button>
            </form>

            <div class="demo">
                <p><strong>Demo Accounts</strong></p>
                <p>Admin: admin / 1234</p>
                <p>Teacher: teacher1 / teacher123</p>
                <p>Student: student1 / student123</p>
            </div>
        </div>
    </div>
</body>
</html>
