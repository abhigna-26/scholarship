<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $app_id = $_POST['application_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];

    $stmt = $pdo->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
    if ($stmt->execute([$status, $remarks, $app_id])) {
        // If approved, create an unpaid payment record if it doesn't exist
        if ($status === 'Approved') {
             // get scholarship amount
             $appStmt = $pdo->prepare("SELECT s.amount FROM applications a JOIN scholarships s ON a.scholarship_id = s.id WHERE a.id = ?");
             $appStmt->execute([$app_id]);
             $appData = $appStmt->fetch();

             $chkStmt = $pdo->prepare("SELECT id FROM payments WHERE application_id = ?");
             $chkStmt->execute([$app_id]);
             if (!$chkStmt->fetch()) {
                 $payStmt = $pdo->prepare("INSERT INTO payments (application_id, amount, status) VALUES (?, ?, 'Unpaid')");
                 $payStmt->execute([$app_id, $appData['amount']]);
             }
        }
        $message = "Application updated successfully.";
    } else {
        $message = "Failed to update application.";
    }
}

// Fetch applications
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;

if ($view_id > 0) {
    // View single application details
    $stmt = $pdo->prepare("
        SELECT a.*, s.title as scholarship_title, st.full_name, st.email 
        FROM applications a 
        JOIN scholarships s ON a.scholarship_id = s.id 
        JOIN students st ON a.student_id = st.id 
        WHERE a.id = ?");
    $stmt->execute([$view_id]);
    $application = $stmt->fetch();

    if (!$application) {
        die("Application not found.");
    }
    
    // Fetch documents
    $docStmt = $pdo->prepare("SELECT * FROM documents WHERE application_id = ?");
    $docStmt->execute([$view_id]);
    $documents = $docStmt->fetchAll();
} else {
    // List all
    $applications = $pdo->query("
        SELECT a.*, s.title as scholarship_title, st.full_name 
        FROM applications a 
        JOIN scholarships s ON a.scholarship_id = s.id 
        JOIN students st ON a.student_id = st.id 
        ORDER BY a.application_date DESC
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - Admin Dashboard</title>
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
                <a href="applications.php" class="nav-link active"><i class="fas fa-file-alt"></i> Applications</a>
                <a href="payments.php" class="nav-link"><i class="fas fa-rupee-sign"></i> Payments</a>
                <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?php if($view_id > 0): ?>
                <div class="topbar">
                    <h2>Application Details - <?php echo htmlspecialchars($application['application_no']); ?></h2>
                    <a href="applications.php" class="btn btn-secondary">Back to List</a>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">Student Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($application['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?></p>
                            
                            <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem; margin-top:2rem;">Scholarship Details</h3>
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($application['scholarship_title']); ?></p>
                            <p><strong>Date Applied:</strong> <?php echo htmlspecialchars($application['application_date']); ?></p>
                            <p><strong>Current Status:</strong> <span class="badge badge-<?php echo strtolower($application['status']); ?>"><?php echo htmlspecialchars($application['status']); ?></span></p>
                        </div>
                        <div>
                            <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">Uploaded Documents</h3>
                            <?php if(count($documents) > 0): ?>
                                <ul style="list-style: none; padding:0;">
                                <?php foreach($documents as $doc): ?>
                                    <li style="margin-bottom: 0.5rem;">
                                        <i class="fas fa-file-pdf" style="color:var(--danger)"></i> 
                                        <a href="../uploads/<?php echo htmlspecialchars($doc['stored_name']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($doc['original_name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No documents uploaded.</p>
                            <?php endif; ?>

                            <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem; margin-top:2rem;">Action</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control" required <?php if($application['status'] == 'Draft') echo 'disabled'; ?>>
                                        <option value="Pending" <?php if($application['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Approved" <?php if($application['status'] == 'Approved') echo 'selected'; ?>>Approve</option>
                                        <option value="Rejected" <?php if($application['status'] == 'Rejected') echo 'selected'; ?>>Reject</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Remarks (shown to student)</label>
                                    <textarea name="remarks" class="form-control" rows="3"><?php echo htmlspecialchars($application['remarks'] ?? ''); ?></textarea>
                                </div>
                                
                                <?php if($application['status'] != 'Draft'): ?>
                                <button type="submit" class="btn btn-primary">Update Application</button>
                                <?php else: ?>
                                <p style="color:var(--danger)">Cannot update Draft applications.</p>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="topbar">
                    <h2>Review Applications</h2>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>App No</th>
                                    <th>Student</th>
                                    <th>Scholarship</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($applications as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['application_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['scholarship_title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['application_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?view=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($applications) === 0): ?>
                                <tr><td colspan="6" style="text-align:center;">No applications found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
