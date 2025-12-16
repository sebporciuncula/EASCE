<?php
require_once '../config.php';
checkAuth(['admin']);

$success = '';
$error = '';

// Get all colleges for dropdown
$colleges_stmt = $conn->query("SELECT DISTINCT department FROM student_profiles WHERE department IS NOT NULL AND department != '' ORDER BY department");
$colleges = $colleges_stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle Actions (Create, Update, Delete, Toggle)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    // --- CREATE USER ---
    if ($action == 'create') {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $user_type = sanitize($_POST['user_type']);
        $fullname = sanitize($_POST['fullname']);
        
        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
        
        if (!preg_match($passwordRegex, $password)) {
            $error = "Password too weak! Must be 8+ chars with Upper, Lower, Number, Symbol.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already exists!";
            } else {
                try {
                    $conn->beginTransaction();
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Map Departments
                    $dept_map = [
                        'registrar' => 1, 'cashier' => 2, 'frontline' => 3, 'faculty' => 4,
                        'osas' => 5, 'library' => 6, 'property' => 7, 'dean' => 8
                    ];
                    $department_id = $dept_map[$user_type] ?? null;
                    
                    // Insert User
                    $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, department_id, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->execute([$email, $hashed_password, $user_type, $department_id]);
                    $user_id = $conn->lastInsertId();
                    
                    // Insert Profile
                    if ($user_type == 'student') {
                        $program = sanitize($_POST['program']);
                        $stmt = $conn->prepare("INSERT INTO student_profiles (user_id, fullname, program) VALUES (?, ?, ?)");
                        $stmt->execute([$user_id, $fullname, $program]);
                    } else if (in_array($user_type, array_keys($dept_map))) {
                        $position = sanitize($_POST['position'] ?? '');
                        $contact = sanitize($_POST['contact_number'] ?? '');
                        $college = in_array($user_type, ['faculty', 'property', 'dean']) ? sanitize($_POST['college_department']) : null;
                        
                        $stmt = $conn->prepare("INSERT INTO staff_profiles (user_id, department_id, fullname, position, contact_number, college_department) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $department_id, $fullname, $position, $contact, $college]);
                        
                        // Handler Permissions
                        $can_verify = ($user_type === 'cashier') ? 1 : 0;
                        $can_release = ($user_type === 'frontline') ? 1 : 0;
                        $can_sign = in_array($user_type, ['registrar', 'faculty', 'osas', 'library', 'property', 'dean']) ? 1 : 0;
                        
                        $stmt = $conn->prepare("INSERT INTO department_handlers (department_id, user_id, handler_role, college_department, can_approve_requests, can_verify_payments, can_release_documents, can_sign_clearance, is_active, assigned_at, assigned_by) VALUES (?, ?, 'staff', ?, 1, ?, ?, ?, 1, NOW(), ?)");
                        $stmt->execute([$department_id, $user_id, $college, $can_verify, $can_release, $can_sign, $_SESSION['user_id']]);
                    }
                    
                    $conn->commit();
                    $success = "User created successfully!";
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
        
    // --- UPDATE USER ---
    } elseif ($action == 'update') {
        $edit_id = (int)$_POST['user_id'];
        $edit_fullname = sanitize($_POST['fullname']);
        $edit_email = sanitize($_POST['email']);
        $edit_type = $_POST['user_type']; // Hidden field, mostly for reference logic
        
        try {
            $conn->beginTransaction();
            
            // 1. Update Email (if changed)
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$edit_email, $edit_id]);
            
            // 2. Update Password (if provided)
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
                if (preg_match($passwordRegex, $password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $edit_id]);
                } else {
                    throw new Exception("New password is too weak.");
                }
            }
            
            // 3. Update Profile Data
            if ($edit_type == 'student') {
                $program = sanitize($_POST['program']);
                $stmt = $conn->prepare("UPDATE student_profiles SET fullname = ?, program = ? WHERE user_id = ?");
                $stmt->execute([$edit_fullname, $program, $edit_id]);
            } else {
                $position = sanitize($_POST['position']);
                $contact = sanitize($_POST['contact_number']);
                $college = isset($_POST['college_department']) ? sanitize($_POST['college_department']) : null;
                
                $stmt = $conn->prepare("UPDATE staff_profiles SET fullname = ?, position = ?, contact_number = ?, college_department = ? WHERE user_id = ?");
                $stmt->execute([$edit_fullname, $position, $contact, $college, $edit_id]);
                
                // Update Handler College if applicable
                if ($college) {
                    $stmt = $conn->prepare("UPDATE department_handlers SET college_department = ? WHERE user_id = ?");
                    $stmt->execute([$college, $edit_id]);
                }
            }
            
            $conn->commit();
            $success = "User updated successfully!";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }

    // --- DELETE / TOGGLE ---
    } elseif ($action == 'delete') {
        $user_id = (int)$_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND id != ?");
        $stmt->execute([$user_id, $_SESSION['user_id']]);
        $success = "User deleted successfully!";
    } elseif ($action == 'toggle_status') {
        $user_id = (int)$_POST['user_id'];
        $new_status = $_POST['status'] == 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND id != ?");
        $stmt->execute([$new_status, $user_id, $_SESSION['user_id']]);
        $success = "User status updated!";
    }
}

// --- FILTER LOGIC ---
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT u.*, 
    COALESCE(sp.fullname, stp.fullname, 'Admin') as fullname,
    COALESCE(sp.program, stp.college_department, stp.position, 'System') as extra_info,
    stp.position, stp.contact_number, sp.program, stp.college_department,
    d.department_name
FROM users u
LEFT JOIN student_profiles sp ON u.id = sp.user_id
LEFT JOIN staff_profiles stp ON u.id = stp.user_id
LEFT JOIN department_handlers dh ON u.id = dh.user_id
LEFT JOIN departments d ON dh.department_id = d.id
WHERE 1=1";

$params = [];

if ($search) {
    $query .= " AND (u.email LIKE ? OR sp.fullname LIKE ? OR stp.fullname LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term]);
}

if ($role_filter) {
    $query .= " AND u.user_type = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - EAS-CE Admin</title>
    
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
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .bg-purple { background-color: #6f42c1 !important; color: white; }
        .bg-teal { background-color: #20c997 !important; color: white; }
        .bg-indigo { background-color: #6610f2 !important; color: white; }
        .bg-orange { background-color: #fd7e14 !important; color: white; }
        
        .password-strength { font-size: 0.75rem; margin-top: 5px; display: flex; gap: 10px; flex-wrap: wrap; }
        .req-item { color: #dc3545; transition: color 0.3s; }
        .req-item.valid { color: #198754; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="Logo" class="sidebar-logo">
            <div><h3>EAS-CE</h3><p>Admin Panel</p></div>
        </div>
         <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="users.php" class="active"><i class="bi bi-people"></i> User Management</a></li>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">User Management</h4>
                <p class="text-muted small">Create, search, and manage system accounts</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-2"></i> Add New User
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="student" <?php echo $role_filter=='student'?'selected':''; ?>>Student</option>
                            <option value="registrar" <?php echo $role_filter=='registrar'?'selected':''; ?>>Registrar</option>
                            <option value="cashier" <?php echo $role_filter=='cashier'?'selected':''; ?>>Cashier</option>
                            <option value="frontline" <?php echo $role_filter=='frontline'?'selected':''; ?>>Frontline</option>
                            <option value="faculty" <?php echo $role_filter=='faculty'?'selected':''; ?>>Faculty</option>
                            <option value="dean" <?php echo $role_filter=='dean'?'selected':''; ?>>Dean</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary w-100">Filter</button>
                    </div>
                    <?php if($search || $role_filter): ?>
                    <div class="col-md-2">
                        <a href="users.php" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show border-start border-success border-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show border-start border-danger border-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>User Info</th>
                            <th>Role / Dept</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <span class="fw-bold text-primary"><?php echo strtoupper(substr($user['fullname'], 0, 1)); ?></span>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                    $colors = [
                                        'admin' => 'danger', 'student' => 'primary', 'registrar' => 'info',
                                        'cashier' => 'success', 'frontline' => 'secondary', 'osas' => 'warning',
                                        'library' => 'teal', 'faculty' => 'purple', 'property' => 'orange', 'dean' => 'indigo'
                                    ];
                                    $color = $colors[$user['user_type']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?> badge-pill mb-1">
                                    <?php echo strtoupper($user['user_type']); ?>
                                </span>
                                <div class="small text-muted fst-italic"><?php echo htmlspecialchars($user['extra_info']); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?> bg-opacity-10 text-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?> border border-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="text-end">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a href="#" class="dropdown-item" onclick='openEditModal(<?php echo json_encode($user); ?>)'>
                                                    <i class="bi bi-pencil-square me-2 text-primary"></i> Edit Details
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-toggle-<?php echo $user['status'] == 'active' ? 'on text-success' : 'off text-muted'; ?> me-2"></i>
                                                        <?php echo $user['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            </li>
                                            
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete user permanently?')">
                                                        <i class="bi bi-trash me-2"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i> Create New Account</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="create">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Full Name</label>
                                <input type="text" class="form-control" name="fullname" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Password</label>
                                <input type="password" class="form-control" name="password" id="newPassword" required>
                                <div class="password-strength" id="passwordStrength">
                                    <span class="req-item" id="len"><i class="bi bi-circle"></i> 8+</span>
                                    <span class="req-item" id="up"><i class="bi bi-circle"></i> Upper</span>
                                    <span class="req-item" id="low"><i class="bi bi-circle"></i> Lower</span>
                                    <span class="req-item" id="num"><i class="bi bi-circle"></i> Num</span>
                                    <span class="req-item" id="sym"><i class="bi bi-circle"></i> Sym</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Role</label>
                                <select class="form-select" name="user_type" id="userType" required>
                                    <option value="">Select Role...</option>
                                    <option value="student">Student</option>
                                    <option value="registrar">Registrar</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="frontline">Frontline</option>
                                    <option value="faculty">Faculty</option>
                                    <option value="dean">Dean</option>
                                    <option value="property">Property</option>
                                    <option value="osas">OSAS</option>
                                    <option value="library">Library</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="dynamicFields" class="mt-4 p-3 bg-light rounded border" style="display:none;">
                            <div id="studentFields" style="display:none;">
                                <label class="form-label">Program</label>
                                <select class="form-select" name="program">
                                    <option value="BSCS">BS Computer Science</option>
                                    <option value="BSIT">BS Information Technology</option>
                                    <option value="BSBA">BS Business Administration</option>
                                </select>
                            </div>
                            <div id="staffFields" style="display:none;">
                                <div class="row g-3">
                                    <div class="col-6"><label class="form-label">Position</label><input type="text" class="form-control" name="position"></div>
                                    <div class="col-6"><label class="form-label">Contact</label><input type="text" class="form-control" name="contact_number"></div>
                                    <div class="col-12" id="collegeFields" style="display:none;">
                                        <label class="form-label">Assigned College</label>
                                        <select class="form-select" name="college_department" id="collegeSelect">
                                            <option value="">Select College...</option>
                                            <?php foreach($colleges as $c): ?><option value="<?=$c?>"><?=$c?></option><?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i> Edit Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <input type="hidden" name="user_type" id="edit_user_type_hidden">
                        
                        <div class="alert alert-info py-2 small">
                            <i class="bi bi-info-circle me-1"></i> User Role cannot be changed. Leave password blank to keep current.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="fullname" id="edit_fullname" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password (Optional)</label>
                                <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" id="edit_user_type_display" readonly disabled>
                            </div>
                        </div>

                        <div id="editDynamicFields" class="mt-3">
                            <div class="row g-3">
                                <div class="col-md-6 edit-staff">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" id="edit_position">
                                </div>
                                <div class="col-md-6 edit-staff">
                                    <label class="form-label">Contact</label>
                                    <input type="text" class="form-control" name="contact_number" id="edit_contact">
                                </div>
                                <div class="col-md-12 edit-student">
                                    <label class="form-label">Program</label>
                                    <select class="form-select" name="program" id="edit_program">
                                        <option value="BSCS">BS Computer Science</option>
                                        <option value="BSIT">BS Information Technology</option>
                                        <option value="BSBA">BS Business Administration</option>
                                    </select>
                                </div>
                                <div class="col-md-12 edit-college">
                                    <label class="form-label">Assigned College</label>
                                    <select class="form-select" name="college_department" id="edit_college">
                                        <option value="">Select College...</option>
                                        <?php foreach($colleges as $c): ?><option value="<?=$c?>"><?=$c?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-warning">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- PASSWORD STRENGTH ---
        const passIn = document.getElementById('newPassword');
        const sBtn = document.getElementById('submitBtn');
        const reqs = { len:/.{8,}/, up:/[A-Z]/, low:/[a-z]/, num:/[0-9]/, sym:/[\W_]/ };

        passIn.addEventListener('input', function(){
            let valid = true;
            for(const [k,r] of Object.entries(reqs)){
                const el = document.getElementById(k);
                if(r.test(this.value)){ el.classList.add('valid'); el.querySelector('i').className='bi bi-check-circle-fill'; }
                else{ el.classList.remove('valid'); el.querySelector('i').className='bi bi-circle'; valid=false; }
            }
            sBtn.disabled = !valid;
        });

        // --- DYNAMIC FIELDS (ADD) ---
        document.getElementById('userType').addEventListener('change', function(){
            const t = this.value;
            document.getElementById('dynamicFields').style.display = t ? 'block' : 'none';
            document.getElementById('studentFields').style.display = t=='student'?'block':'none';
            document.getElementById('staffFields').style.display = t!='student'?'block':'none';
            document.getElementById('collegeFields').style.display = ['dean','faculty','property'].includes(t)?'block':'none';
            document.getElementById('collegeSelect').required = ['dean','faculty','property'].includes(t);
        });

        // --- EDIT MODAL POPULATOR ---
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_fullname').value = user.fullname;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_user_type_hidden').value = user.user_type;
            document.getElementById('edit_user_type_display').value = user.user_type.toUpperCase();

            // Toggle visibility based on type
            const isStudent = user.user_type === 'student';
            const isCollegeRole = ['dean','faculty','property'].includes(user.user_type);

            document.querySelectorAll('.edit-student').forEach(e => e.style.display = isStudent ? 'block' : 'none');
            document.querySelectorAll('.edit-staff').forEach(e => e.style.display = !isStudent ? 'block' : 'none');
            document.querySelectorAll('.edit-college').forEach(e => e.style.display = isCollegeRole ? 'block' : 'none');

            // Set Values
            if(isStudent) {
                document.getElementById('edit_program').value = user.program || '';
            } else {
                document.getElementById('edit_position').value = user.position || '';
                document.getElementById('edit_contact').value = user.contact_number || '';
                if(isCollegeRole) {
                    document.getElementById('edit_college').value = user.college_department || '';
                }
            }

            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
    </script>
</body>
</html>