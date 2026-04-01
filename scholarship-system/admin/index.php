<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch stats
$applications_count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();
$approved_count = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Approved'")->fetchColumn();
$scholarships_open = $pdo->query("SELECT COUNT(*) FROM scholarships WHERE status = 'Open'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Scholarship System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3 style="margin:0; color:var(--primary);">Admin Portal</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="scholarships.php" class="nav-link"><i class="fas fa-graduation-cap"></i> Scholarships</a>
                <a href="applications.php" class="nav-link"><i class="fas fa-file-alt"></i> Applications</a>
                <a href="payments.php" class="nav-link"><i class="fas fa-rupee-sign"></i> Payments</a>
                <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <div>
                   <span class="badge badge-approved">Admin Logged In</span>
                </div>
            </div>

            <div class="grid-3">
                <div class="card stat-card">
                    <h3><?php echo $applications_count; ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="card stat-card">
                    <h3 style="color: var(--warning);"><?php echo $pending_count; ?></h3>
                    <p>Pending Applications</p>
                </div>
                <div class="card stat-card">
                    <h3 style="color: var(--success);"><?php echo $approved_count; ?></h3>
                    <p>Approved Applications</p>
                </div>
                <div class="card stat-card">
                    <h3 style="color: var(--info);"><?php echo $scholarships_open; ?></h3>
                    <p>Open Scholarships</p>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
