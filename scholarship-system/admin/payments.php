<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    $payment_id = $_POST['payment_id'];
    $stmt = $pdo->prepare("UPDATE payments SET status = 'Paid', processed_at = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt->execute([$payment_id])) {
        $message = "Payment marked as paid.";
    } else {
        $message = "Failed to update payment status.";
    }
}

// Fetch payments with application and student details
$payments = $pdo->query("
    SELECT p.*, a.application_no, st.full_name, s.title as scholarship_title 
    FROM payments p 
    JOIN applications a ON p.application_id = a.id 
    JOIN students st ON a.student_id = st.id
    JOIN scholarships s ON a.scholarship_id = s.id
    ORDER BY p.status DESC, p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - Admin Dashboard</title>
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
                <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="scholarships.php" class="nav-link"><i class="fas fa-graduation-cap"></i> Scholarships</a>
                <a href="applications.php" class="nav-link"><i class="fas fa-file-alt"></i> Applications</a>
                <a href="payments.php" class="nav-link active"><i class="fas fa-rupee-sign"></i> Payments</a>
                <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h2>Manage Payments</h2>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>App No</th>
                                <th>Student</th>
                                <th>Scholarship</th>
                                <th>Amount</th>
                                <th>Date Processed</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($payments as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['application_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['scholarship_title']); ?></td>
                                <td>$<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo $row['processed_at'] ? date('M d, Y', strtotime($row['processed_at'])) : '-'; ?></td>
                                <td>
                                    <?php if($row['status'] == 'Paid'): ?>
                                        <span class="badge badge-paid">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-unpaid">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['status'] == 'Unpaid'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="payment_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="return confirm('Mark this scholarship as paid?');">Mark Paid</button>
                                    </form>
                                    <?php else: ?>
                                    <span style="color:var(--gray); font-size:0.9rem;">No action needed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($payments) === 0): ?>
                            <tr><td colspan="7" style="text-align:center;">No payments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
