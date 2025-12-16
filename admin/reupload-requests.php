<?php
require_once '../config.php';
checkAuth(['admin']);

// Get all reupload requests
$stmt = $conn->prepare("
    SELECT 
        prr.*,
        dr.request_type,
        dr.payment_amount,
        CONCAT(sp.fullname) as student_name,
        sp.student_id,
        u.email,
        p.payment_method,
        p.reference_number,
        p.payment_proof
    FROM payment_reupload_requests prr
    JOIN document_requests dr ON prr.request_id = dr.id
    JOIN student_profiles sp ON prr.student_id = sp.user_id
    JOIN users u ON prr.student_id = u.id
    LEFT JOIN payments p ON dr.id = p.request_id
    ORDER BY 
        CASE prr.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        prr.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll();

// Count pending requests
$pendingCount = count(array_filter($requests, function($r) { return $r['status'] == 'pending'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Re-upload Requests - EAS-CE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        .request-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fafbfc;
            transition: all 0.3s;
        }

        .request-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .student-info h5 {
            margin: 0;
            color: #1e293b;
            font-size: 18px;
        }

        .student-info small {
            color: #64748b;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #475569;
        }

        .info-item i {
            color: #667eea;
            font-size: 18px;
        }

        .reason-box {
            background: #fff7ed;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }

        .reason-box strong {
            color: #92400e;
        }

        .payment-details {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .payment-details h6 {
            color: #1e293b;
            margin-bottom: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .btn-approve {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-approve:hover {
            background: #059669;
        }

        .btn-reject {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-reject:hover {
            background: #dc2626;
        }

        .btn-view {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .proof-preview-modal .modal-dialog {
            max-width: 90%;
            width: auto;
        }

        .proof-preview-modal .modal-body {
            padding: 0;
            text-align: center;
            background: #000;
        }

        .proof-preview-modal img {
            max-width: 100%;
            max-height: 85vh;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .filter-tab {
            padding: 8px 20px;
            border: none;
            background: none;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .filter-tab:hover {
            color: #667eea;
        }

        .filter-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .badge-count {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }

        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="EAS-CE Logo" class="sidebar-logo">
            <div>
                <h3>EAS-CE</h3>
                <p>Admin Panel</p>
            </div>
        </div>
        
         <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="users.php"><i class="bi bi-people"></i> User Management</a></li>
        <li><a href="students.php"><i class="bi bi-person-badge"></i> Students</a></li>
        <li><a href="staff.php"><i class="bi bi-briefcase"></i> Staff</a></li>
        <li><a href="documents.php"><i class="bi bi-file-text"></i> Document Types</a></li>
        <li><a href="settings.php"><i class="bi bi-gear"></i> System Settings</a></li>
        <li><a href="transaction_logs.php"><i class="bi bi-receipt"></i> Transaction Logs</a></li>
        <li><a href="logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
        <li><a href="../student/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-bar">
            <h4>Payment Re-upload Requests</h4>
            <p class="text-muted mb-0">Review and approve student payment re-upload requests</p>
        </div>

        <div class="activity-card">
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterRequests('all')">
                    All Requests <span class="badge-count"><?php echo count($requests); ?></span>
                </button>
                <button class="filter-tab" onclick="filterRequests('pending')">
                    Pending <span class="badge-count"><?php echo $pendingCount; ?></span>
                </button>
                <button class="filter-tab" onclick="filterRequests('approved')">
                    Approved
                </button>
                <button class="filter-tab" onclick="filterRequests('rejected')">
                    Rejected
                </button>
            </div>

            <?php if (count($requests) > 0): ?>
                <?php foreach ($requests as $req): ?>
                <div class="request-item" data-status="<?php echo $req['status']; ?>">
                    <div class="request-header">
                        <div class="student-info">
                            <h5><?php echo htmlspecialchars($req['student_name']); ?></h5>
                            <small>
                                <i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($req['student_id']); ?> | 
                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($req['email']); ?>
                            </small>
                        </div>
                        <span class="status-badge status-<?php echo $req['status']; ?>">
                            <?php echo strtoupper($req['status']); ?>
                        </span>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <i class="bi bi-file-earmark-text"></i>
                            <span><strong>Document:</strong> <?php echo htmlspecialchars($req['request_type']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-cash"></i>
                            <span><strong>Amount:</strong> ₱<?php echo number_format($req['payment_amount'], 2); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-calendar"></i>
                            <span><strong>Requested:</strong> <?php echo date('M d, Y g:i A', strtotime($req['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-hash"></i>
                            <span><strong>Request ID:</strong> #<?php echo $req['request_id']; ?></span>
                        </div>
                    </div>

                    <div class="reason-box">
                        <strong><i class="bi bi-exclamation-triangle"></i> Reason for Re-upload:</strong><br>
                        <?php echo htmlspecialchars($req['reason']); ?>
                        <?php if ($req['notes']): ?>
                            <br><small><strong>Additional Notes:</strong> <?php echo htmlspecialchars($req['notes']); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="payment-details">
                        <h6><i class="bi bi-credit-card-2-front"></i> Current Payment Details</h6>
                        <div class="info-grid">
                            <div class="info-item">
                                <i class="bi bi-wallet2"></i>
                                <span><strong>Method:</strong> <?php echo htmlspecialchars($req['payment_method']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-hash"></i>
                                <span><strong>Reference:</strong> <?php echo htmlspecialchars($req['reference_number']); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($req['admin_response']): ?>
                    <div class="alert alert-info mt-3">
                        <strong><i class="bi bi-chat-left-text"></i> Admin Response:</strong><br>
                        <?php echo htmlspecialchars($req['admin_response']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <?php if ($req['payment_proof']): ?>
                        <button class="btn-view" onclick="viewProof('../uploads/payments/<?php echo htmlspecialchars($req['payment_proof']); ?>')">
                            <i class="bi bi-eye"></i> View Current Proof
                        </button>
                        <?php endif; ?>

                        <?php if ($req['status'] == 'pending'): ?>
                        <button class="btn-approve" onclick="handleRequest(<?php echo $req['id']; ?>, 'approved')">
                            <i class="bi bi-check-circle"></i> Approve Re-upload
                        </button>
                        <button class="btn-reject" onclick="showRejectModal(<?php echo $req['id']; ?>)">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>No Re-upload Requests</h5>
                    <p>There are no payment re-upload requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Proof Preview Modal -->
    <div class="modal fade proof-preview-modal" id="proofModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Proof</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="proofImage" src="" alt="Payment Proof">
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Re-upload Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectForm">
                    <div class="modal-body">
                        <input type="hidden" id="reject_request_id" name="request_id">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Please provide a reason for rejecting this re-upload request. The student will be notified.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="admin_response" rows="4" required placeholder="Explain why the re-upload request is being rejected..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const proofModal = new bootstrap.Modal(document.getElementById('proofModal'));
    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));

    function viewProof(imagePath) {
        document.getElementById('proofImage').src = imagePath;
        proofModal.show();
    }

    function showRejectModal(requestId) {
        document.getElementById('reject_request_id').value = requestId;
        document.getElementById('rejectForm').reset();
        document.getElementById('reject_request_id').value = requestId;
        rejectModal.show();
    }

    function handleRequest(requestId, action) {
        const message = action === 'approved' 
            ? 'Are you sure you want to APPROVE this re-upload request? The student will be able to upload new payment details.' 
            : 'Are you sure you want to reject this request?';
        
        if (!confirm(message)) return;

        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', action);

        fetch('process-reupload-request.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert(action === 'approved' ? 'Re-upload request approved!' : 'Request processed!');
                location.reload();
            } else {
                alert('Error: ' + d.message);
            }
        })
        .catch(() => alert('Error occurred'));
    }

    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'rejected');

        fetch('process-reupload-request.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Re-upload request rejected!');
                rejectModal.hide();
                location.reload();
            } else {
                alert('Error: ' + d.message);
            }
        })
        .catch(() => alert('Error occurred'));
    });

    function filterRequests(status) {
        const items = document.querySelectorAll('.request-item');
        const tabs = document.querySelectorAll('.filter-tab');
        
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        items.forEach(item => {
            if (status === 'all' || item.dataset.status === status) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>