<?php
require_once '../config.php';
checkAuth(['admin']);

// =======================================
// GET STATS FOR DASHBOARD CARDS
// =======================================
$stmt = $conn->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'active') as total_students,
    (SELECT COUNT(*) FROM users WHERE user_type IN ('registrar', 'cashier', 'frontline') AND status = 'active') as total_staff,
    (SELECT COUNT(*) FROM document_requests WHERE status = 'pending') as pending_requests,
    (SELECT COUNT(*) FROM document_requests WHERE status IN ('processing', 'ready')) as processing_docs
");
$stats = $stmt->fetch();

// =======================================
// FILTER LOGIC
// =======================================
$filter = $_GET['filter'] ?? '';

// Default: Recent Activities
$activities_query = "SELECT sl.*, u.email, u.user_type 
    FROM system_logs sl 
    LEFT JOIN users u ON sl.user_id = u.id";

// Apply filters for Activity Feed (only used if no specific table is shown, 
// OR you can use this logic to show specific logs based on filter. 
// For this design, we will switch views based on filter)

$activities_query .= " ORDER BY sl.created_at DESC LIMIT 10";
$stmt = $conn->prepare($activities_query);
$stmt->execute();
$activities = $stmt->fetchAll();

// =======================================
// DATA FETCHING FUNCTIONS
// =======================================

function getStudentDetails($conn) {
    $stmt = $conn->prepare("
        SELECT u.*, sp.fullname, sp.program, sp.student_id 
        FROM users u
        JOIN student_profiles sp ON u.id = sp.user_id
        WHERE u.user_type = 'student' AND u.status = 'active'
        ORDER BY u.created_at DESC LIMIT 20
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getStaffDetails($conn) {
    $stmt = $conn->prepare("
        SELECT 
            u.*, sp.fullname, sp.contact_number, 
            d.department_name
        FROM users u
        JOIN staff_profiles sp ON u.id = sp.user_id
        LEFT JOIN departments d ON sp.department_id = d.id
        WHERE u.user_type IN ('registrar', 'cashier', 'frontline') 
          AND u.status = 'active'
        ORDER BY u.created_at DESC LIMIT 20
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getDocumentRequests($conn, $status_condition) {
    // $status_condition example: "status = 'pending'" or "status IN ('processing', 'ready')"
    $stmt = $conn->prepare("
        SELECT dr.*, sp.fullname 
        FROM document_requests dr
        JOIN student_profiles sp ON dr.student_id = sp.user_id
        WHERE $status_condition
        ORDER BY dr.created_at DESC LIMIT 20
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// =======================================
// DETERMINE VIEW DATA
// =======================================
$filtered_data = [];
$data_title = "Recent System Activity";

if ($filter == 'students') {
    $filtered_data = getStudentDetails($conn);
    $data_title = "Active Students";
} elseif ($filter == 'staff') {
    $filtered_data = getStaffDetails($conn);
    $data_title = "Active Staff Members";
} elseif ($filter == 'pending') {
    $filtered_data = getDocumentRequests($conn, "dr.status = 'pending'");
    $data_title = "Pending Document Requests";
} elseif ($filter == 'processing') {
    $filtered_data = getDocumentRequests($conn, "dr.status IN ('processing', 'ready')");
    $data_title = "Processing Documents";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EAS-CE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/style.css">
    
    <style>
        /* Specific overrides for clickable cards */
        .stat-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .stat-link:hover .stats-card {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/logo.png" class="sidebar-logo">
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

<div class="main-content">
    
    <div class="header-bar">
        <h4>Admin Dashboard</h4>
        <p class="text-muted mb-0">Welcome back! Here's what's happening today.</p>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-md-3">
            <a href="dashboard.php?filter=students" class="stat-link">
                <div class="stats-card <?= $filter == 'students' ? 'active-students' : '' ?>">
                    <div class="stats-icon icon-blue"><i class="bi bi-people"></i></div>
                    <div class="stats-number text-primary"><?= $stats['total_students']; ?></div>
                    <div class="stats-label">Total Students</div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="dashboard.php?filter=staff" class="stat-link">
                <div class="stats-card <?= $filter == 'staff' ? 'active-staff' : '' ?>">
                    <div class="stats-icon icon-green"><i class="bi bi-person-check"></i></div>
                    <div class="stats-number text-success"><?= $stats['total_staff']; ?></div>
                    <div class="stats-label">Staff Members</div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="dashboard.php?filter=pending" class="stat-link">
                <div class="stats-card <?= $filter == 'pending' ? 'active-pending' : '' ?>">
                    <div class="stats-icon icon-orange"><i class="bi bi-clock-history"></i></div>
                    <div class="stats-number text-warning"><?= $stats['pending_requests']; ?></div>
                    <div class="stats-label">Pending Requests</div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="dashboard.php?filter=processing" class="stat-link">
                <div class="stats-card <?= $filter == 'processing' ? 'active-processing' : '' ?>">
                    <div class="stats-icon icon-purple"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="stats-number text-info"><?= $stats['processing_docs']; ?></div>
                    <div class="stats-label">Processing Docs</div>
                </div>
            </a>
        </div>

    </div>

    <div class="activity-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i> <?= $data_title ?>
            </h5>
            <?php if ($filter): ?>
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filter
                </a>
            <?php endif; ?>
        </div>

        <?php if ($filter == 'students'): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Program</th>
                            <th>Date Registered</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_data as $row): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($row['student_id']) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['program']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="students.php?view=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($filter == 'staff'): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Department</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_data as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <?php if($row['department_name']): ?>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($row['department_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['contact_number']) ?></td>
                            <td><span class="badge bg-success">Active</span></td>
                            <td class="text-end">
                                <a href="staff.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($filter == 'pending' || $filter == 'processing'): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Student Name</th>
                            <th>Document Type</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Date Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_data as $row): ?>
                        <tr>
                            <td class="fw-bold">#<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['request_type']) ?></td>
                            <td>
                                <?php 
                                    $badge_class = match($row['status']) {
                                        'pending' => 'bg-warning text-dark',
                                        'processing' => 'bg-info text-white',
                                        'ready' => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst($row['status']) ?></span>
                            </td>
                            <td>₱<?= number_format($row['payment_amount'], 2) ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php if (empty($activities)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No recent activities found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activities as $log): ?>
                    <div class="activity-item d-flex align-items-start">
                        <div class="me-3 mt-1">
                            <?php if ($log['user_type'] == 'admin'): ?>
                                <span class="badge bg-danger rounded-circle p-2"><i class="bi bi-shield-lock"></i></span>
                            <?php elseif ($log['user_type'] == 'student'): ?>
                                <span class="badge bg-primary rounded-circle p-2"><i class="bi bi-person"></i></span>
                            <?php else: ?>
                                <span class="badge bg-success rounded-circle p-2"><i class="bi bi-briefcase"></i></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($log['email'] ?? 'Unknown User') ?></strong>
                                <small class="text-muted"><?= date('M d, h:i A', strtotime($log['created_at'])) ?></small>
                            </div>
                            <p class="mb-0 text-dark"><?= htmlspecialchars($log['action']) ?></p>
                            <small class="text-muted"><?= htmlspecialchars($log['details']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="mt-3 text-center">
                <a href="logs.php" class="btn btn-sm btn-outline-secondary">View All Logs</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>