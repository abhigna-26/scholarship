<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

// Fetch open scholarships
$scholarships = $pdo->query("SELECT * FROM scholarships WHERE status = 'Open' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Scholarships - Student Dashboard</title>
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
                <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="scholarships.php" class="nav-link active"><i class="fas fa-search"></i> Find Scholarships</a>
                <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h2>Available Scholarships</h2>
            </div>

            <div class="grid-3">
                <?php foreach($scholarships as $row): ?>
                <div class="card" style="display:flex; flex-direction:column;">
                    <h3 style="color:var(--primary); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="color:var(--gray); font-size: 0.9rem; margin-bottom: 1rem; flex: 1;">
                        <?php echo htmlspecialchars($row['description']); ?>
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <span style="font-weight: 600; color: var(--success);"><i class="fas fa-rupee-sign"></i> <?php echo number_format($row['amount'], 2); ?></span>
                        <span style="font-size: 0.85rem; color: var(--danger);"><i class="far fa-clock"></i> Ends: <?php echo date('M d, Y', strtotime($row['deadline'])); ?></span>
                    </div>
                    <?php
                        // Check if student already applied
                        $stmt = $pdo->prepare("SELECT status FROM applications WHERE student_id = ? AND scholarship_id = ?");
                        $stmt->execute([$_SESSION['user_id'], $row['id']]);
                        $has_applied = $stmt->fetch();
                    ?>
                    <?php if($has_applied): ?>
                        <div class="btn btn-secondary" style="text-align:center; opacity: 0.8; cursor: not-allowed;">
                            <?php if($has_applied['status'] == 'Draft') echo 'Draft Saved'; else echo 'Already Applied'; ?>
                        </div>
                    <?php else: ?>
                        <a href="apply.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="text-align:center;">Apply Now</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if(count($scholarships) === 0): ?>
                <p style="text-align:center; color:var(--gray); margin-top: 3rem;">No open scholarships available at the moment.</p>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
