<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

$scholarship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;

$is_view = false;
$application = null;

if ($view_id > 0) {
    $is_view = true;
    $stmt = $pdo->prepare("SELECT a.*, s.title, s.description FROM applications a JOIN scholarships s ON a.scholarship_id = s.id WHERE a.id = ? AND a.student_id = ?");
    $stmt->execute([$view_id, $user_id]);
    $application = $stmt->fetch();
    if(!$application){ die("Application not found."); }
} else if ($scholarship_id > 0) {
    // Check if scholarship exists
    $stmt = $pdo->prepare("SELECT * FROM scholarships WHERE id = ?");
    $stmt->execute([$scholarship_id]);
    $scholarship = $stmt->fetch();
    if(!$scholarship){ die("Scholarship not found."); }
    
    // Check if draft exists
    $stmt = $pdo->prepare("SELECT id, status FROM applications WHERE student_id = ? AND scholarship_id = ?");
    $stmt->execute([$user_id, $scholarship_id]);
    $application = $stmt->fetch();
    
    if ($application && $application['status'] !== 'Draft') {
        // Redirect to view
        header("Location: apply.php?view=" . $application['id']);
        exit;
    }
} else {
    header("Location: scholarships.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_view) {
    $action = $_POST['submit_action']; // 'Draft' or 'Submit'
    $status = ($action === 'Submit') ? 'Pending' : 'Draft';
    
    $pdo->beginTransaction();
    try {
        if ($application) {
            // Update draft
            $app_id = $application['id'];
            $update = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $update->execute([$status, $app_id]);
        } else {
            // Create new
            $app_no = 'APP' . date('Ymd') . rand(1000,9999);
            $insert = $pdo->prepare("INSERT INTO applications (application_no, student_id, scholarship_id, status) VALUES (?, ?, ?, ?)");
            $insert->execute([$app_no, $user_id, $scholarship_id, $status]);
            $app_id = $pdo->lastInsertId();
            $application = ['id' => $app_id]; // for document upload
        }
        
        // Handle document upload
        if (isset($_FILES['document']) && $_FILES['document']['error'] == UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['document']['tmp_name'];
            $file_name = $_FILES['document']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            if(in_array($file_ext, $allowed)) {
                $new_name = uniqid('doc_') . '.' . $file_ext;
                $dest = '../uploads/' . $new_name;
                
                if (move_uploaded_file($file_tmp, $dest)) {
                    $docStmt = $pdo->prepare("INSERT INTO documents (application_id, original_name, stored_name) VALUES (?, ?, ?)");
                    $docStmt->execute([$app_id, $file_name, $new_name]);
                }
            } else {
                throw new Exception("Invalid file type. Only PDF, DOC, JPG, PNG allowed.");
            }
        }
        
        $pdo->commit();
        if ($status === 'Pending') {
            header("Location: index.php");
            exit;
        } else {
            $message = "Draft saved successfully.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch documents for view
$documents = [];
if ($application) {
    $docStmt = $pdo->prepare("SELECT * FROM documents WHERE application_id = ?");
    $docStmt->execute([$application['id']]);
    $documents = $docStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application - Scholarship System</title>
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
                <a href="scholarships.php" class="nav-link <?php echo !$is_view ? 'active' : ''; ?>"><i class="fas fa-search"></i> Find Scholarships</a>
                <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h2><?php echo $is_view ? 'View Application' : 'Apply for Scholarship'; ?></h2>
                <a href="<?php echo $is_view ? 'index.php' : 'scholarships.php'; ?>" class="btn btn-secondary">Back</a>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px;">
                <h3 style="color:var(--primary); margin-bottom: 1rem;">
                    <?php echo $is_view ? htmlspecialchars($application['title']) : htmlspecialchars($scholarship['title']); ?>
                </h3>
                
                <?php if($is_view): ?>
                    <div style="margin-bottom: 2rem;">
                        <p><strong>App Number:</strong> <?php echo htmlspecialchars($application['application_no']); ?></p>
                        <p><strong>Status:</strong> <span class="badge badge-<?php echo strtolower($application['status']); ?>"><?php echo htmlspecialchars($application['status']); ?></span></p>
                        <?php if($application['remarks']): ?>
                        <div style="background-color:var(--light); padding:1rem; border-left:4px solid var(--warning); margin-top:1rem;">
                            <strong>Admin Remarks:</strong>
                            <p style="margin-top:0.5rem;"><?php echo htmlspecialchars($application['remarks']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <h4 style="margin-bottom: 1rem;">Uploaded Documents</h4>
                <?php if(count($documents) > 0): ?>
                    <ul style="list-style: none; padding:0; margin-bottom: 2rem;">
                    <?php foreach($documents as $doc): ?>
                        <li style="margin-bottom: 0.5rem; padding: 0.5rem; background: var(--light); border-radius: 0.25rem;">
                            <i class="fas fa-file-pdf" style="color:var(--danger)"></i> 
                            <a href="../uploads/<?php echo htmlspecialchars($doc['stored_name']); ?>" target="_blank">
                                <?php echo htmlspecialchars($doc['original_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="margin-bottom: 2rem; color: var(--gray);">No documents uploaded yet.</p>
                <?php endif; ?>

                <?php if(!$is_view): ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Upload Additional Document (PDF, DOCX, JPG, PNG)</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small style="color:var(--gray);">Max size: 5MB</small>
                    </div>
                    
                    <div style="display:flex; gap:1rem; margin-top: 2rem;">
                        <button type="submit" name="submit_action" value="Draft" class="btn btn-secondary">Save as Draft</button>
                        <button type="submit" name="submit_action" value="Submit" class="btn btn-primary">Submit Application</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
