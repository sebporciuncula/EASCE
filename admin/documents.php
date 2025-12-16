<?php
require_once '../config.php';
checkAuth(['admin']);

$success = '';
$error = '';

// Handle document actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $name = sanitize($_POST['name']);
        $price = floatval($_POST['price']);
        $processing_days = (int)$_POST['processing_days'];
        $description = sanitize($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO document_types (name, price, processing_days, description) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $price, $processing_days, $description])) {
            logActivity($conn, $_SESSION['user_id'], 'Document Type Created', "Created: $name");
            $success = "Document type created successfully!";
        } else {
            $error = "Failed to create document type.";
        }
    } elseif ($action == 'update') {
        $id = (int)$_POST['doc_id'];
        $name = sanitize($_POST['name']);
        $price = floatval($_POST['price']);
        $processing_days = (int)$_POST['processing_days'];
        $description = sanitize($_POST['description']);
        
        $stmt = $conn->prepare("UPDATE document_types SET name = ?, price = ?, processing_days = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$name, $price, $processing_days, $description, $id])) {
            logActivity($conn, $_SESSION['user_id'], 'Document Type Updated', "Updated: $name");
            $success = "Document type updated successfully!";
        } else {
            $error = "Failed to update document type.";
        }
    } elseif ($action == 'toggle') {
        $id = (int)$_POST['doc_id'];
        $is_active = (int)$_POST['is_active'];
        $new_status = $is_active ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE document_types SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        
        $success = "Document status updated!";
    } elseif ($action == 'delete') {
        $id = (int)$_POST['doc_id'];
        
        $stmt = $conn->prepare("DELETE FROM document_types WHERE id = ?");
        if ($stmt->execute([$id])) {
            logActivity($conn, $_SESSION['user_id'], 'Document Type Deleted', "Deleted ID: $id");
            $success = "Document type deleted successfully!";
        } else {
            $error = "Failed to delete document type.";
        }
    }
}

// Get all document types
$stmt = $conn->query("SELECT * FROM document_types ORDER BY name ASC");
$documents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Types - EAS-CE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="EAS-CE Logo" class="sidebar-logo">
            <div>
                <h3>EAS-CE</h3>
                <p>Admin Panel</p>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="bi bi-people"></i> User Management</a></li>
            <li><a href="students.php"><i class="bi bi-person-badge"></i> Students</a></li>
            <li><a href="staff.php"><i class="bi bi-briefcase"></i> Staff</a></li>
            <li><a href="documents.php" class="active"><i class="bi bi-file-text"></i> Document Types</a></li>
            <li><a href="settings.php"><i class="bi bi-gear"></i> System Settings</a></li>
            <li><a href="transaction_logs.php"><i class="bi bi-receipt"></i> Transaction Logs</a></li>
            <li><a href="logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
            <li><a href="../student/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-card border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4>Document Types Management</h4>
                    <p class="text-muted small mb-0">Manage services, pricing, and processing times.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDocModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Document Type
                </button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach ($documents as $doc): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="doc-card">
                        
                        <div class="doc-card-header">
                            <h5 class="doc-card-title text-truncate" title="<?php echo htmlspecialchars($doc['name']); ?>">
                                <?php echo htmlspecialchars($doc['name']); ?>
                            </h5>
                            <?php if ($doc['is_active']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill border border-success border-opacity-25">
                                    <i class="bi bi-check-circle-fill me-1"></i> Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill border border-secondary border-opacity-25">
                                    <i class="bi bi-x-circle-fill me-1"></i> Inactive
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="doc-card-body">
                            <p class="text-muted mb-0 small" style="min-height: 2.6rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($doc['description'] ?: 'No description provided.'); ?>
                            </p>
                            
                            <div class="doc-info-grid">
                                <div class="doc-info-item">
                                    <div class="doc-info-icon bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>
                                    <div class="doc-info-text">
                                        <small>Price</small>
                                        <strong>₱<?php echo number_format($doc['price'], 2); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="doc-info-item">
                                    <div class="doc-info-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-clock-history"></i>
                                    </div>
                                    <div class="doc-info-text">
                                        <small>Process</small>
                                        <strong><?php echo $doc['processing_days']; ?> Days</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="doc-card-footer">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="editDoc(<?php echo htmlspecialchars(json_encode($doc)); ?>)"
                                    title="Edit Details">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $doc['is_active']; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $doc['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" 
                                        title="<?php echo $doc['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="bi bi-toggle-<?php echo $doc['is_active'] ? 'on' : 'off'; ?>"></i>
                                </button>
                            </form>
                            
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="confirmDelete(<?php echo $doc['id']; ?>)"
                                title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addDocModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Document Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label">Document Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Certificate of Grades" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="price" step="0.01" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Processing Days</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="processing_days" placeholder="1" required>
                                    <span class="input-group-text">Days</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Enter brief description..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editDocModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Document Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="doc_id" id="edit_doc_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Document Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Processing Days</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="processing_days" id="edit_processing_days" required>
                                    <span class="input-group-text">Days</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteDocModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="doc_id" id="delete_doc_id">
                        <p class="mb-0">Are you sure you want to delete this document type? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Function
        function editDoc(doc) {
            document.getElementById('edit_doc_id').value = doc.id;
            document.getElementById('edit_name').value = doc.name;
            document.getElementById('edit_price').value = doc.price;
            document.getElementById('edit_processing_days').value = doc.processing_days;
            document.getElementById('edit_description').value = doc.description;
            
            new bootstrap.Modal(document.getElementById('editDocModal')).show();
        }

        // New: Delete Function (Opens Modal instead of default Alert)
        function confirmDelete(id) {
            document.getElementById('delete_doc_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteDocModal')).show();
        }
    </script>
</body>
</html>