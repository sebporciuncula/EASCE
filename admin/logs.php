<?php
require_once '../config.php';
checkAuth(['admin']);

// --- Filtering Variables ---
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// --- Build Query ---
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(sl.action LIKE ? OR sl.details LIKE ? OR u.email LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term]);
}

if ($type_filter) {
    $where_conditions[] = "u.user_type = ?";
    $params[] = $type_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(sl.created_at) = ?";
    $params[] = $date_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total logs
$total_sql = "SELECT COUNT(sl.id) as total FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id " . $where_clause;
$total_stmt = $conn->prepare($total_sql);
$total_stmt->execute($params);
$total = $total_stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Get logs with pagination
$log_sql = "SELECT sl.*, u.email, u.user_type 
    FROM system_logs sl 
    LEFT JOIN users u ON sl.user_id = u.id 
    {$where_clause}
    ORDER BY sl.created_at DESC 
    LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($log_sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// --- Helper for Badge Colors ---
function getUserTypeColor($type) {
    $colors = [
        'admin' => 'danger', 'student' => 'primary', 'registrar' => 'info',
        'cashier' => 'success', 'frontline' => 'secondary', 'osas' => 'warning',
        'library' => 'teal', 'faculty' => 'purple', 'property' => 'orange', 'dean' => 'indigo', 'system' => 'dark'
    ];
    return $colors[$type] ?? 'secondary';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - EAS-CE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }

        /* Log Item Styling */
        .log-item-container {
            border-left: 3px solid #dee2e6;
            padding-left: 15px;
        }

        .log-item {
            position: relative;
            background: #fcfcfc;
            border: 1px solid #f1f1f1;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .log-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .log-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 20px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #0f52ba;
            border: 3px solid white;
            z-index: 10;
        }

        .log-timestamp {
            font-size: 0.75rem;
            color: #6c757d;
            line-height: 1.3;
        }

        .log-action {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 5px;
        }

        .log-details {
            font-size: 0.85rem;
            color: #6c757d;
            word-wrap: break-word;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pagination .page-item.active .page-link {
            background-color: #0f52ba;
            border-color: #0f52ba;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="Logo" class="sidebar-logo">
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
        <li><a href="documents.php"><i class="bi bi-file-text"></i> Document Types</a></li>
        <li><a href="settings.php"><i class="bi bi-gear"></i> System Settings</a></li>
        <li><a href="transaction_logs.php"><i class="bi bi-receipt"></i> Transaction Logs</a></li>
        <li><a href="logs.php" class="active"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
        <li><a href="../student/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i> System Activity Logs</h4>
                <p class="text-muted small">Comprehensive audit trail of all user actions.</p>
            </div>
        </div>

        <div class="content-card mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">Search (Action/Email)</label>
                    <input type="text" name="search" class="form-control" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">User Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="admin" <?php echo $type_filter=='admin'?'selected':''; ?>>Admin</option>
                        <option value="student" <?php echo $type_filter=='student'?'selected':''; ?>>Student</option>
                        <option value="registrar" <?php echo $type_filter=='registrar'?'selected':''; ?>>Registrar</option>
                        <option value="cashier" <?php echo $type_filter=='cashier'?'selected':''; ?>>Cashier</option>
                        <option value="frontline" <?php echo $type_filter=='frontline'?'selected':''; ?>>Frontline</option>
                        <option value="osas" <?php echo $type_filter=='osas'?'selected':''; ?>>OSAS</option>
                        <option value="library" <?php echo $type_filter=='library'?'selected':''; ?>>Library</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Specific Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
            <div class="mt-3">
                 <span class="text-muted small">
                    Showing <?php echo count($logs); ?> logs (Total: <?php echo $total; ?>) | Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                 </span>
            </div>
        </div>

        <div class="content-card">
            <?php if (count($logs) > 0): ?>
            <div class="log-item-container">
                <?php foreach ($logs as $log): ?>
                <div class="log-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="user-info">
                                <span class="badge bg-<?php echo getUserTypeColor($log['user_type'] ?? 'system'); ?>">
                                    <?php echo ucfirst($log['user_type'] ?? 'System'); ?>
                                </span>
                                <strong class="text-primary small"><?php echo htmlspecialchars($log['email'] ?? 'System User'); ?></strong>
                            </div>
                            
                            <div class="log-action mt-2">
                                <i class="bi bi-arrow-right-short"></i> <?php echo htmlspecialchars($log['action']); ?>
                            </div>
                            
                            <?php if ($log['details']): ?>
                                <div class="log-details">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </div>
                            <?php endif; ?>

                            <small class="text-muted mt-1 d-block">
                                <i class="bi bi-geo-alt"></i> IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                            </small>
                        </div>
                        
                        <div class="text-end">
                            <div class="log-timestamp">
                                <strong><?php echo date('M d, Y', strtotime($log['created_at'])); ?></strong><br>
                                <span class="text-muted"><?php echo date('h:i:s A', strtotime($log['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-3">No activity logs found matching your filters.</p>
                </div>
            <?php endif; ?>

            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php 
                    $base_url = "logs.php?search=" . urlencode($search) . "&type=" . urlencode($type_filter) . "&date=" . urlencode($date_filter) . "&page="; 
                    ?>
                    
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url . ($page - 1); ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url . ($page + 1); ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>