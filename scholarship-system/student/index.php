<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get counts
$total_apps = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ?");
$total_apps->execute([$user_id]);
$total_apps = $total_apps->fetchColumn();

$approved_apps = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status = 'Approved'");
$approved_apps->execute([$user_id]);
$approved_apps = $approved_apps->fetchColumn();

$draft_apps = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status = 'Draft'");
$draft_apps->execute([$user_id]);
$draft_apps = $draft_apps->fetchColumn();

// Fetch applications
$stmt = $pdo->prepare("
    SELECT a.*, s.title as scholarship_title 
    FROM applications a 
    JOIN scholarships s ON a.scholarship_id = s.id 
    WHERE a.student_id = ? 
    ORDER BY a.application_date DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Scholarship System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3 style="margin:0; color:var(--primary);">Student Portal</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Home</a>
                <a href="scholarships.php" class="nav-link"><i class="fas fa-search"></i> Find Scholarships</a>
                <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
                <div>
                   <span class="badge badge-pending">Student Profile</span>
                </div>
            </div>

            <div class="grid-3" style="margin-bottom: 2rem;">
                <div class="card stat-card">
                    <h3><?php echo $total_apps; ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="card stat-card">
                    <h3 style="color: var(--success);"><?php echo $approved_apps; ?></h3>
                    <p>Approved</p>
                </div>
                <div class="card stat-card">
                    <h3 style="color: var(--gray);"><?php echo $draft_apps; ?></h3>
                    <p>Drafts</p>
                </div>
            </div>

            <h3 style="margin-bottom: 1rem;">My Applications</h3>
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>App No</th>
                                <th>Scholarship</th>
                                <th>Date Applied</th>
                                <th>Status</th>
                                <th>Admin Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($applications as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['application_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['scholarship_title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['application_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $row['remarks'] ? htmlspecialchars($row['remarks']) : '<em style="color:var(--gray);font-size:0.9rem;">No remarks yet</em>'; ?>
                                </td>
                                <td>
                                    <?php if($row['status'] == 'Draft'): ?>
                                        <a href="apply.php?id=<?php echo $row['scholarship_id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Edit Draft</a>
                                    <?php else: ?>
                                        <a href="apply.php?view=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($applications) === 0): ?>
                            <tr><td colspan="6" style="text-align:center;">You haven't applied to any scholarships yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
