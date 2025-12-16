<?php
require_once '../config.php';
checkAuth(['admin']);

// ============================
// GET STAFF DATA (FIXED VERSION)
// ============================
$stmt = $conn->query("
    SELECT 
        u.*,
        COALESCE(sp.fullname, u.email) AS fullname,
        sp.position,
        sp.contact_number,
        sp.created_at AS profile_created,
        d.department_code,
        d.department_name
    FROM users u
    LEFT JOIN staff_profiles sp ON u.id = sp.user_id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.user_type IN (
    'registrar',
    'cashier',
    'frontline',
    'property',
    'faculty',
    'library',
    'osas',
    'dean'
)
    ORDER BY fullname ASC
");

$staff = $stmt->fetchAll();

// ============================
// STAFF COUNT
// ============================
$stats_stmt = $conn->query("
    SELECT 
        COUNT(*) AS total,
        COUNT(CASE WHEN user_type = 'registrar' THEN 1 END) AS registrar,
        COUNT(CASE WHEN user_type = 'cashier' THEN 1 END) AS cashier,
        COUNT(CASE WHEN user_type = 'frontline' THEN 1 END) AS frontline
    FROM users
    WHERE user_type IN ('registrar', 'cashier', 'frontline')
");

$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - EAS-CE</title>
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
        <li><a href="staff.php" class="active"><i class="bi bi-briefcase"></i> Staff</a></li>
        <li><a href="documents.php"><i class="bi bi-file-text"></i> Document Types</a></li>
        <li><a href="settings.php"><i class="bi bi-gear"></i> System Settings</a></li>
        <li><a href="transaction_logs.php"><i class="bi bi-receipt"></i> Transaction Logs</a></li>
        <li><a href="logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
        <li><a href="../student/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
    </div>

    <div class="main-content">
        <div class="content-card">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Staff Management</h4>

                <div class="d-flex gap-2">
                    <select class="form-select" id="filterSelect" style="max-width: 150px;">
                        <option value="all">All Fields</option>
                        <option value="0">Name</option>
                        <option value="1">Email</option>
                        <option value="2">Department</option>
                        <option value="3">Position</option>
                        <option value="4">Contact</option>
                        <option value="5">Status</option>
                    </select>

                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search staff...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="staffTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Added</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($staff as $member): ?>
                        <tr>
                            <td><?= htmlspecialchars($member['fullname']); ?></td>

                            <td><?= htmlspecialchars($member['email']); ?></td>

                            <td>
                                <?php if ($member['department_name']): ?>
                                    <span class="badge bg-<?=
                                        $member['department_code'] == 'REGISTRAR' ? 'primary' :
                                        ($member['department_code'] == 'CASHIER' ? 'success' : 'info')
                                    ?>">
                                        <?= htmlspecialchars($member['department_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unassigned</span>
                                <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($member['position'] ?? 'N/A'); ?></td>

                            <td><?= htmlspecialchars($member['contact_number'] ?? 'N/A'); ?></td>

                            <td>
                                <span class="badge bg-<?= $member['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?= ucfirst($member['status']); ?>
                                </span>
                            </td>

                            <td><?= date('M d, Y', strtotime($member['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Search with filter
        const searchInput = document.getElementById('searchInput');
        const filterSelect = document.getElementById('filterSelect');
        const table = document.getElementById('staffTable');

        function performSearch() {
            const searchValue = searchInput.value.toLowerCase();
            const filterValue = filterSelect.value;
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let show = false;
                
                if (filterValue === 'all') {
                    const combined = Array.from(cells).map(td => td.textContent).join(' ').toLowerCase();
                    show = combined.includes(searchValue);
                } else {
                    const col = parseInt(filterValue);
                    show = cells[col]?.textContent.toLowerCase().includes(searchValue);
                }
                
                row.style.display = show ? '' : 'none';
            }
        }

        searchInput.addEventListener('keyup', performSearch);
        filterSelect.addEventListener('change', performSearch);
    </script>

</body>
</html>