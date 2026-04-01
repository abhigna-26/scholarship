<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); // could be username or email
    $password = $_POST['password'];
    
    // Check Admin first
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$identifier]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = $admin['username'];
        header("Location: admin/index.php");
        exit;
    }
    
    // Check Student
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$identifier]);
    $student = $stmt->fetch();
    
    if ($student && password_verify($password, $student['password_hash'])) {
        $_SESSION['user_id'] = $student['id'];
        $_SESSION['role'] = 'student';
        $_SESSION['full_name'] = $student['full_name'];
        header("Location: student/index.php");
        exit;
    }
    
    $error = "Invalid credentials. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scholarship System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background-color: var(--light);">
    <div class="auth-container">
        <div class="auth-card">
            <h2 style="text-align: center; margin-bottom: 0.5rem;">Welcome Back</h2>
            <p style="text-align: center; color: var(--gray); margin-bottom: 2rem;">Sign in to continue</p>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="identifier">Email or Username</label>
                    <input type="text" id="identifier" name="identifier" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
            </form>
            
            <p style="text-align: center; margin-top: 2rem;">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            <p style="text-align: center; margin-top: 1rem;">
                <a href="index.php">← Back to Home</a>
            </p>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
