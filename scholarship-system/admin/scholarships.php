<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Handle Add Scholarship
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $deadline = $_POST['deadline'];
    
    $stmt = $pdo->prepare("INSERT INTO scholarships (title, description, amount, deadline) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$title, $description, $amount, $deadline])) {
         $message = "Scholarship added successfully.";
    } else {
         $message = "Failed to add scholarship.";
    }
}

// Handle Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT status FROM scholarships WHERE id = ?");
    $stmt->execute([$id]);
    $scholarship = $stmt->fetch();
    if ($scholarship) {
        $new_status = $scholarship['status'] === 'Open' ? 'Closed' : 'Open';
        $updateStmt = $pdo->prepare("UPDATE scholarships SET status = ? WHERE id = ?");
        $updateStmt->execute([$new_status, $id]);
        header("Location: scholarships.php");
        exit;
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM scholarships WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: scholarships.php");
    exit;
}

// Fetch all scholarships
$scholarships = $pdo->query("SELECT * FROM scholarships ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scholarships - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #fff; padding: 2rem; border-radius: 0.5rem;
            width: 100%; max-width: 500px;
        }
    </style>
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
                <a href="scholarships.php" class="nav-link active"><i class="fas fa-graduation-cap"></i> Scholarships</a>
                <a href="applications.php" class="nav-link"><i class="fas fa-file-alt"></i> Applications</a>
                <a href="payments.php" class="nav-link"><i class="fas fa-rupee-sign"></i> Payments</a>
                <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h2>Manage Scholarships</h2>
                <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">
                    <i class="fas fa-plus"></i> Add Scholarship
                </button>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Amount</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($scholarships as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td>$<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['deadline']); ?></td>
                                <td>
                                    <?php if($row['status'] == 'Open'): ?>
                                        <span class="badge badge-approved">Open</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejected">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?action=toggle&id=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">
                                        Toggle
                                    </a>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="return confirm('Delete this scholarship?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($scholarships) === 0): ?>
                            <tr><td colspan="5" style="text-align:center;">No scholarships found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h3 style="margin-bottom:1rem;">Add New Scholarship</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deadline</label>
                    <input type="date" name="deadline" class="form-control" required>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:1rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Scholarship</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
