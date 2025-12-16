<?php
require_once '../config.php';
checkAuth(['admin']);

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings = $_POST['setting'];
    
    // Handle checkboxes that might not be posted
    // Removed 'allow_registration' from this list
    $checkbox_settings = ['maintenance_mode']; 
    foreach ($checkbox_settings as $checkbox) {
        if (!isset($settings[$checkbox])) {
            $settings[$checkbox] = '0';
        }
    }
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_by) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?");
        $stmt->execute([$key, $value, $_SESSION['user_id'], $value, $_SESSION['user_id']]);
    }
    
    logActivity($conn, $_SESSION['user_id'], 'Settings Updated', 'Updated system settings');
    $success = "Settings updated successfully!";
    
    // Refresh settings data after update
    $stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Get current settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Default settings if not set (Removed allow_registration default)
$default_settings = [
    'site_name' => 'EAS-CE',
    'site_email' => 'admin@dyci.edu.ph',
    'maintenance_mode' => '0',
    'max_requests_per_day' => '5',
    'payment_instructions' => 'Please pay at the Cashier Office during office hours.',
    'office_hours' => 'Monday to Friday, 8:00 AM - 5:00 PM'
];

foreach ($default_settings as $key => $value) {
    if (!isset($settings_data[$key])) {
        $settings_data[$key] = $value;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - EAS-CE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .settings-header {
            padding: 20px 25px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-header h5 {
            margin: 0;
            font-weight: 600;
            color: #343a40;
        }
        
        .settings-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            font-size: 0.9rem;
        }

        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .alert-maintenance {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .info-card {
            background: linear-gradient(135deg, #0f52ba 0%, #003366 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
        }
        
        .info-card h5 { margin-bottom: 20px; font-weight: 600; }
        .info-item { margin-bottom: 10px; font-size: 0.9rem; display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px; }
        .info-item:last-child { border-bottom: none; }
        .info-label { opacity: 0.8; }
        .info-val { font-weight: 600; }
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
        <li><a href="settings.php" class="active"><i class="bi bi-gear"></i> System Settings</a></li>
        <li><a href="transaction_logs.php"><i class="bi bi-receipt"></i> Transaction Logs</a></li>
        <li><a href="logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
        <li><a href="../student/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">System Configuration</h4>
                <p class="text-muted small">Manage general settings and system parameters</p>
            </div>
        </div>

        <?php if ($settings_data['maintenance_mode'] == '1'): ?>
            <div class="alert-maintenance shadow-sm">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div>
                    <strong>Maintenance Mode Active</strong><br>
                    <small>Only administrators can currently access the system. Students and staff are locked out.</small>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm border-start border-success border-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm border-start border-danger border-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-header">
                            <i class="bi bi-sliders text-primary fs-5"></i>
                            <h5>General Settings</h5>
                        </div>
                        <div class="settings-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">System Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-app"></i></span>
                                        <input type="text" class="form-control" name="setting[site_name]" 
                                            value="<?php echo htmlspecialchars($settings_data['site_name']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Support Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" name="setting[site_email]" 
                                            value="<?php echo htmlspecialchars($settings_data['site_email']); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Office Hours Display</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-clock"></i></span>
                                        <input type="text" class="form-control" name="setting[office_hours]" 
                                            value="<?php echo htmlspecialchars($settings_data['office_hours']); ?>">
                                    </div>
                                    <div class="form-text">This text is displayed on the footer or contact pages.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-header">
                            <i class="bi bi-toggles text-warning fs-5"></i>
                            <h5>Controls & Restrictions</h5>
                        </div>
                        <div class="settings-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label d-block fw-bold mb-2">System Status</label>
                                    <div class="form-check form-switch p-0 m-0 d-flex align-items-center gap-2">
                                        <input class="form-check-input m-0" type="checkbox" role="switch"
                                            id="maintenance_mode" style="width: 3em; height: 1.5em;"
                                            name="setting[maintenance_mode]" 
                                            value="1" 
                                            <?php echo $settings_data['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label ms-2" for="maintenance_mode">
                                            Enable Maintenance Mode
                                        </label>
                                    </div>
                                    <div class="form-text mt-2">Activate this when performing updates. Users will see a maintenance page.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Daily Request Limit (Per Student)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-file-earmark-break"></i></span>
                                        <input type="number" class="form-control" name="setting[max_requests_per_day]" 
                                            value="<?php echo htmlspecialchars($settings_data['max_requests_per_day']); ?>" min="1" max="50">
                                    </div>
                                    <div class="form-text">Prevents spamming of document requests.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-header">
                            <i class="bi bi-credit-card text-success fs-5"></i>
                            <h5>Payment Configuration</h5>
                        </div>
                        <div class="settings-body">
                            <div class="mb-3">
                                <label class="form-label">Payment Instructions</label>
                                <textarea class="form-control" name="setting[payment_instructions]" rows="4"><?php echo htmlspecialchars($settings_data['payment_instructions']); ?></textarea>
                                <div class="form-text">These instructions appear on the student's payment page. You can include bank details here.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mb-5">
                        <button type="reset" class="btn btn-light border px-4">Reset Changes</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="info-card shadow-sm">
                    <h5 class="d-flex align-items-center gap-2">
                        <i class="bi bi-server"></i> Server Info
                    </h5>
                    
                    <div class="info-item">
                        <span class="info-label">PHP Version</span>
                        <span class="info-val"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Server Software</span>
                        <span class="info-val text-truncate" style="max-width: 150px;"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Database</span>
                        <span class="info-val">MySQL</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Protocol</span>
                        <span class="info-val"><?php echo isset($_SERVER['HTTPS']) ? 'HTTPS' : 'HTTP'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">System Time</span>
                        <span class="info-val"><?php echo date('H:i'); ?></span>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-secondary">Quick Tips</h6>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2"><i class="bi bi-info-circle me-2 text-primary"></i> <strong>Maintenance Mode</strong> overrides all other access rights except for Admins.</li>
                            <li class="mb-2"><i class="bi bi-info-circle me-2 text-primary"></i> <strong>Request Limits</strong> reset automatically at midnight server time.</li>
                            <li><i class="bi bi-info-circle me-2 text-primary"></i> Changes to <strong>Payment Instructions</strong> reflect immediately on the student portal.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>