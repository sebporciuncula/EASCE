<?php
require_once '../config.php';
checkAuth(['admin']);

// ==========================================
// 1. DATA FETCHING LOGIC
// ==========================================
function getTransactionData($conn, $filters) {
    $dept_filter = $filters['department'] ?? 'all';
    $role_filter = $filters['role'] ?? 'all';
    $start_date  = $filters['start_date'] ?? '';
    $end_date    = $filters['end_date'] ?? '';
    
    $queries = [];

    // Date Condition Helper
    $date_condition = "";
    if ($start_date) {
        $date_condition .= " AND DATE(created_at_col) >= '$start_date'";
    }
    if ($end_date) {
        $date_condition .= " AND DATE(created_at_col) <= '$end_date'";
    }

    // --- 1. STUDENT TRANSACTIONS ---
    if ($dept_filter == 'all' || $dept_filter == 'STUDENT') {
        if ($role_filter == 'all' || $role_filter == 'student') {
            // Payments
            $sql = "SELECT p.created_at as created_at_col, 
                    CONCAT(sp.fullname, ' (Student)') COLLATE utf8mb4_general_ci as actor_name,
                    'Student' COLLATE utf8mb4_general_ci as actor_dept,
                    'Payment Submitted' COLLATE utf8mb4_general_ci as action_type,
                    CONCAT('Amount: ₱', p.amount) COLLATE utf8mb4_general_ci as details,
                    CONCAT('Req #', p.request_id) COLLATE utf8mb4_general_ci as ref_number
                    FROM payments p JOIN student_profiles sp ON p.student_id = sp.user_id WHERE 1=1";
            $queries[] = $sql . str_replace('created_at_col', 'p.created_at', $date_condition);

            // Requests
            $sql = "SELECT dr.created_at as created_at_col,
                    CONCAT(sp.fullname, ' (Student)') COLLATE utf8mb4_general_ci as actor_name,
                    'Student' COLLATE utf8mb4_general_ci as actor_dept,
                    'Request Created' COLLATE utf8mb4_general_ci as action_type,
                    dr.request_type COLLATE utf8mb4_general_ci as details,
                    CONCAT('Req #', dr.id) COLLATE utf8mb4_general_ci as ref_number
                    FROM document_requests dr JOIN student_profiles sp ON dr.student_id = sp.user_id WHERE 1=1";
            $queries[] = $sql . str_replace('created_at_col', 'dr.created_at', $date_condition);
        }
    }

    // --- 2. CASHIER TRANSACTIONS ---
    if ($dept_filter == 'all' || $dept_filter == 'CASHIER') {
        if ($role_filter == 'all' || $role_filter == 'staff') {
            $sql = "SELECT cl.action_date as created_at_col, 
                    CONCAT(sp.fullname, ' (Cashier)') COLLATE utf8mb4_general_ci as actor_name,
                    'Cashier Office' COLLATE utf8mb4_general_ci as actor_dept,
                    CONCAT('Payment ', cl.action) COLLATE utf8mb4_general_ci as action_type,
                    CONCAT('Verified: ₱', cl.amount) COLLATE utf8mb4_general_ci as details,
                    CONCAT('Req #', cl.request_id) COLLATE utf8mb4_general_ci as ref_number
                    FROM cashier_logs cl JOIN staff_profiles sp ON cl.cashier_id = sp.user_id WHERE 1=1";
            $queries[] = $sql . str_replace('created_at_col', 'cl.action_date', $date_condition);
        }
    }

    // --- 3. REGISTRAR TRANSACTIONS ---
    if ($dept_filter == 'all' || $dept_filter == 'REGISTRAR') {
        if ($role_filter == 'all' || $role_filter == 'staff') {
            $sql = "SELECT rl.action_date as created_at_col, 
                    CONCAT(sp.fullname, ' (Registrar)') COLLATE utf8mb4_general_ci as actor_name,
                    'Registrar Office' COLLATE utf8mb4_general_ci as actor_dept,
                    CONCAT('Process: ', rl.action) COLLATE utf8mb4_general_ci as action_type,
                    rl.notes COLLATE utf8mb4_general_ci as details,
                    CONCAT('Req #', rl.request_id) COLLATE utf8mb4_general_ci as ref_number
                    FROM request_logs rl JOIN staff_profiles sp ON rl.registrar_id = sp.user_id WHERE 1=1";
            $queries[] = $sql . str_replace('created_at_col', 'rl.action_date', $date_condition);
        }
    }

    // --- 4. FRONTLINE TRANSACTIONS ---
    if ($dept_filter == 'all' || $dept_filter == 'FRONTLINE') {
        if ($role_filter == 'all' || $role_filter == 'staff') {
            $sql = "SELECT dr.released_date as created_at_col,
                    CONCAT(sp.fullname, ' (Frontline)') COLLATE utf8mb4_general_ci as actor_name,
                    'Frontline Services' COLLATE utf8mb4_general_ci as actor_dept,
                    'Document Released' COLLATE utf8mb4_general_ci as action_type,
                    dr.request_type COLLATE utf8mb4_general_ci as details,
                    CONCAT('Req #', dr.id) COLLATE utf8mb4_general_ci as ref_number
                    FROM document_requests dr JOIN staff_profiles sp ON dr.released_by = sp.user_id
                    WHERE dr.status = 'released' AND dr.released_date IS NOT NULL";
            $queries[] = $sql . str_replace('created_at_col', 'dr.released_date', $date_condition);
        }
    }

    if (empty($queries)) return [];

    $final_sql = implode(" UNION ALL ", $queries) . " ORDER BY created_at_col DESC LIMIT 500";
    
    try {
        $stmt = $conn->prepare($final_sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return []; 
    }
}

// ==========================================
// 2. HANDLE AJAX REQUESTS (Return HTML Rows)
// ==========================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $logs = getTransactionData($conn, $_GET);
    
    if (count($logs) > 0) {
        foreach($logs as $log) {
            $actionClass = 'text-dark';
            $icon = 'bi-pencil';
            
            if(strpos($log['action_type'], 'Payment') !== false) {
                $actionClass = 'text-success fw-bold';
                $icon = 'bi-cash';
            } elseif(strpos($log['action_type'], 'Released') !== false) {
                $actionClass = 'text-primary fw-bold';
                $icon = 'bi-box-seam';
            }

            echo '<tr>
                <td class="text-muted small">' . date('M d, Y h:i A', strtotime($log['created_at_col'])) . '</td>
                <td class="fw-bold">' . htmlspecialchars($log['actor_name']) . '</td>
                <td><span class="badge bg-secondary">' . htmlspecialchars($log['actor_dept']) . '</span></td>
                <td><span class="' . $actionClass . '"><i class="bi ' . $icon . '"></i> ' . htmlspecialchars($log['action_type']) . '</span></td>
                <td class="small">' . htmlspecialchars($log['details'] ?: '-') . '</td>
                <td><span class="badge bg-light text-dark border">' . htmlspecialchars($log['ref_number']) . '</span></td>
            </tr>';
        }
    } else {
        echo '<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-clipboard-x fs-1 d-block mb-3"></i>No transactions found matching filters.</td></tr>';
    }
    exit; // Stop execution here for AJAX
}

// ==========================================
// 3. HANDLE EXPORT
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $filename = "Transaction_Report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Actor', 'Role/Dept', 'Action Type', 'Details', 'Ref #']);
    $rows = getTransactionData($conn, $_GET);
    foreach ($rows as $row) {
        fputcsv($output, [$row['created_at_col'], $row['actor_name'], $row['actor_dept'], $row['action_type'], $row['details'], $row['ref_number']]);
    }
    fclose($output);
    exit();
}

// Initial Load
$logs = getTransactionData($conn, $_GET);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Logs - EAS-CE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        @media print {
            @page { size: landscape; margin: 0.5cm; }
            .sidebar, .header-bar, .filter-section, .no-print { display: none !important; }
            .main-content { margin: 0 !important; padding: 20px !important; width: 100%; }
            .card { border: none !important; box-shadow: none !important; }
            table { width: 100% !important; font-size: 10pt; border-collapse: collapse; }
            th, td { border: 1px solid #333; padding: 6px; }
            .badge { border: none; color: #000 !important; font-weight: bold; padding: 0; }
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
            .print-logo { width: 60px; height: 60px; margin-bottom: 10px; }
        }
        .print-header { display: none; }
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
        <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="users.php"><i class="bi bi-people"></i> User Management</a></li>
        <li><a href="students.php"><i class="bi bi-person-badge"></i> Students</a></li>
        <li><a href="staff.php"><i class="bi bi-briefcase"></i> Staff</a></li>
        <li><a href="documents.php"><i class="bi bi-file-text"></i> Document Types</a></li>
        <li><a href="settings.php"><i class="bi bi-gear"></i> System Settings</a></li>
        <li><a href="transaction_logs.php" class="active"><i class="bi bi-receipt"></i> Transaction Logs</a></li>
        <li><a href="logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
        <li><a href="../student/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="header-bar">
        <h4>Transaction Logs</h4>
        <p class="text-muted mb-0">Real-time financial and processing records.</p>
    </div>

    <div class="content-card filter-section mb-4">
        <form id="filterForm" class="row g-3 align-items-end">
            
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted">Scope</label>
                <select name="role" id="role" class="form-control form-control-sm" onchange="loadData()">
                    <option value="all">All (Staff & Students)</option>
                    <option value="student">Students Only</option>
                    <option value="staff">Staff Only</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted">Transaction Of</label>
                <select name="department" id="department" class="form-control form-control-sm" onchange="loadData()">
                    <option value="all">All Core Transactions</option>
                    <option value="STUDENT">Student Transactions</option>
                    <option value="CASHIER">Cashier Office</option>
                    <option value="REGISTRAR">Registrar Office</option>
                    <option value="FRONTLINE">Frontline Services</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold small text-muted">Date From</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" onchange="loadData()">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold small text-muted">Date To</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" onchange="loadData()">
            </div>

            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="loadData()"><i class="bi bi-filter"></i> Refresh</button>
                    <div class="dropdown w-100">
                        <button class="btn btn-success btn-sm w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="bi bi-printer"></i> Print PDF</a></li>
                            <li><a class="dropdown-item" href="#" id="exportLink"><i class="bi bi-file-earmark-excel"></i> Export Excel</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="print-header">
        <img src="../assets/logo.png" class="print-logo">
        <h3>EAS-CE Transaction Report</h3>
        <p class="mb-1"><strong>Generated:</strong> <?= date('F j, Y h:i A') ?></p>
    </div>

    <div class="content-card">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="15%">Date & Time</th>
                        <th width="20%">Actor</th>
                        <th width="15%">Role/Dept</th>
                        <th width="20%">Transaction/Action</th>
                        <th width="20%">Details</th>
                        <th width="10%">Ref #</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php 
                    if (count($logs) > 0) {
                        foreach($logs as $log) {
                            $actionClass = 'text-dark';
                            $icon = 'bi-pencil';
                            if(strpos($log['action_type'], 'Payment') !== false) {
                                $actionClass = 'text-success fw-bold';
                                $icon = 'bi-cash';
                            } elseif(strpos($log['action_type'], 'Released') !== false) {
                                $actionClass = 'text-primary fw-bold';
                                $icon = 'bi-box-seam';
                            }
                            echo '<tr>
                                <td class="text-muted small">' . date('M d, Y h:i A', strtotime($log['created_at_col'])) . '</td>
                                <td class="fw-bold">' . htmlspecialchars($log['actor_name']) . '</td>
                                <td><span class="badge bg-secondary">' . htmlspecialchars($log['actor_dept']) . '</span></td>
                                <td><span class="' . $actionClass . '"><i class="bi ' . $icon . '"></i> ' . htmlspecialchars($log['action_type']) . '</span></td>
                                <td class="small">' . htmlspecialchars($log['details'] ?: '-') . '</td>
                                <td><span class="badge bg-light text-dark border">' . htmlspecialchars($log['ref_number']) . '</span></td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-clipboard-x fs-1 d-block mb-3"></i>No transactions found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initial Setup for Export Link
    updateExportLink();

    function loadData() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');

        // Loading State
        document.getElementById('tableBody').innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading transactions...</p></td></tr>';

        fetch('transaction_logs.php?' + params.toString())
            .then(response => response.text())
            .then(html => {
                document.getElementById('tableBody').innerHTML = html;
                updateExportLink();
            })
            .catch(err => {
                console.error('Error fetching data:', err);
                document.getElementById('tableBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">Error loading data.</td></tr>';
            });
    }

    function updateExportLink() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData);
        params.append('export', 'excel');
        document.getElementById('exportLink').href = 'transaction_logs.php?' + params.toString();
    }
</script>
</body>
</html>