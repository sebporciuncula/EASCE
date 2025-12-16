<?php
require_once '../config.php';
checkAuth(['admin']);

// --- HANDLE ALERTS ---
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// --- PAGINATION SETUP ---
$limit = 50; // Max items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- FETCH DATA ---

// 1. Get Masterlist with Pagination
// First, count total records for pagination buttons
$count_stmt = $conn->query("SELECT COUNT(*) FROM masterlist_students");
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Then fetch the actual limited data
$master_stmt = $conn->prepare("SELECT * FROM masterlist_students ORDER BY fullname ASC LIMIT :limit OFFSET :offset");
$master_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$master_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$master_stmt->execute();
$masterlist = $master_stmt->fetchAll();

// 2. Get Registered Users
$user_stmt = $conn->query("SELECT u.*, sp.fullname, sp.program, sp.student_id, sp.year_level, sp.contact_number 
    FROM users u
    JOIN student_profiles sp ON u.id = sp.user_id
    WHERE u.user_type = 'student'
    ORDER BY sp.fullname ASC");
$registered_students = $user_stmt->fetchAll();

// 3. Get Statistics
$stats_stmt = $conn->query("SELECT 
    (SELECT COUNT(*) FROM masterlist_students) as total_masterlist,
    (SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'active') as active_users,
    (SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'pending') as pending_users
");
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - EAS-CE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <li><a href="students.php" class="active"><i class="bi bi-person-badge"></i> Students</a></li>
        <li><a href="staff.php"><i class="bi bi-briefcase"></i> Staff</a></li>
        <li><a href="documents.php"><i class="bi bi-file-text"></i> Document Types</a></li>
        <li><a href="settings.php"><i class="bi bi-gear"></i> System Settings</a></li>
        <li><a href="transaction_logs.php"><i class="bi bi-receipt"></i> Transaction Logs</a></li>
        <li><a href="logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
        <li><a href="../student/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
    </div>

    <div class="main-content">
        
        <?php if($success_msg): ?>
            <script>Swal.fire('Success', '<?php echo htmlspecialchars($success_msg); ?>', 'success');</script>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <script>Swal.fire('Error', '<?php echo htmlspecialchars($error_msg); ?>', 'error');</script>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6>Total in Masterlist</h6>
                        <h3><?php echo $stats['total_masterlist']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>Active Accounts</h6>
                        <h3><?php echo $stats['active_users']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6>Pending Approval</h6>
                        <h3><?php echo $stats['pending_users']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Student Masterlist</h4>
                    <small class="text-muted">Official list of enrolled students (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importStudentModal">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Import CSV
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-person-plus"></i> Add Student
                    </button>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4 ms-auto">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search Masterlist...">
                        <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($masterlist as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($student['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($student['department']); ?></td>
                            <td><?php echo htmlspecialchars($student['program']); ?></td>
                            <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $student['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($masterlist)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No students in masterlist yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav aria-label="Masterlist Pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Previous</a>
                    </li>

                    <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>

        </div>
        
        <div class="content-card mt-4">
            <h5 class="mb-3">Registered Accounts (System Users)</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Account Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registered_students as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="process_masterlist.php" method="POST">
                    <input type="hidden" name="action" value="add_single">
                    <div class="modal-header">
                        <h5 class="modal-title">Add to Masterlist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Student ID <span class="text-danger">*</span></label>
                            <input type="text" name="student_id" class="form-control" required placeholder="e.g. 2021-00001">
                        </div>
                        <div class="mb-3">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control" required placeholder="Last Name, First Name M.I.">
                        </div>
                        <div class="mb-3">
                            <label>Department</label>
                            <select name="department" class="form-select" required>
                                <option value="">Select Department</option>
                                <option value="College of Computer Studies">College of Computer Studies</option>
                                <option value="College of Accountancy">College of Accountancy</option>
                                <option value="College of Education">College of Education</option>
                                <option value="College of Business and Administration">College of Business and Administration</option>
                                <option value="College of Health and Science">College of Health and Science</option>
                                <option value="College of Hospitality Management and Tourism">College of Hospitality Management and Tourism</option>
                                <option value="College of Maritime Education">College of Maritime Education</option>
                                <option value="College of Art and Sciences">College of Art and Sciences</option>
                                <option value="School of Mechanical Engineering">School of Mechanical Engineering</option>
                                <option value="School of Psychology">School of Psychology</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Program</label>
                            <input type="text" name="program" class="form-control" required placeholder="e.g. BS Information Technology">
                        </div>
                        <div class="mb-3">
                            <label>Year Level</label>
                            <select name="year_level" class="form-select" required>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="Irregular">Irregular</option>
                                <option value="Alumni">Alumni / Graduate</option> 
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold text-primary">Student Status</label>
                            <select name="status" class="form-select border-primary" required>
                                <option value="active">Active (Currently Enrolled)</option>
                                <option value="graduated">Graduated (Alumni)</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <small class="text-muted">Select "Graduated" for Alumni.</small>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="process_masterlist.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_csv">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Import Masterlist</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        
                        <div class="text-end mb-3">
                            <a href="download_template.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-download"></i> Download CSV Template
                            </a>
                        </div>

                        <div class="alert alert-info small">
                            <strong><i class="bi bi-info-circle"></i> Instructions:</strong><br>
                            1. <a href="download_template.php" class="alert-link">Download the template</a> above.<br>
                            2. Fill in the student details (Do not change the header names).<br>
                            3. Save the file as <b>CSV (Comma delimited)</b>.<br>
                            4. Upload the file below.<br>
                            <br>
                            <strong>Required Columns (in order):</strong><br>
                            <code>StudentID, FullName, Department, Program, YearLevel, Status</code>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <div class="form-text text-muted">Only .csv files are accepted.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('studentsTable');

        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().includes(searchValue)) {
                        found = true;
                        break;
                    }
                }
                row.style.display = found ? '' : 'none';
            }
        });
    </script>
</body>
</html>