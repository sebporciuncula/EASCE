<?php
// Include email helper functions (kung meron man, pero defined na sa baba ang logic)
// require_once __DIR__ . '/email_helper.php'; 

// Database Configuration
$host = 'localhost';
$db   = 'easce_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

date_default_timezone_set('Asia/Manila');

// Define constants for database (used in other parts)
define('DB_HOST', $host);
define('DB_NAME', $db);
define('DB_USER', $user);
define('DB_PASS', $pass);

// Site Configuration
// SIGURADUHIN NA TAMA ANG PORT DITO (e.g. localhost:8080 kung iba port mo)
define('SITE_URL', 'http://localhost/eas-ce'); 
define('SITE_NAME', 'EAS-CE');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'easece.dyci@gmail.com');
define('SMTP_PASS', 'prvjjfwoklhtmvqs');
define('SMTP_FROM', 'easece.dyci@gmail.com');
define('SMTP_FROM_NAME', 'EAS-CE System');

define('PAYMONGO_SECRET_KEY', 'sk_test_nfEBP8DeAV18YjYNj5KDKnFD');

// PayMongo Public Key (for client-side if needed)
define('PAYMONGO_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');

// Debug mode for PayMongo transactions
define('PAYMONGO_DEBUG_MODE', true);
// PDO Connection Options
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Database Connection
try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Sanitize function
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Email sending function using PHPMailer
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $message) {
        // Path to PHPMailer files
        $phpmailer_path = __DIR__ . '/PHPMailer-master/src/';
        
        if (file_exists($phpmailer_path . 'PHPMailer.php')) {
            require_once $phpmailer_path . 'PHPMailer.php';
            require_once $phpmailer_path . 'SMTP.php';
            require_once $phpmailer_path . 'Exception.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                // Optional: Disable SSL verification for localhost testing
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // Recipients
                $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                $mail->addAddress($to);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;
                
                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
                return false;
            }
        } else {
            error_log("PHPMailer not found at: " . $phpmailer_path);
            return sendEmailSimple($to, $subject, $message);
        }
    }
}

// Alternative: Simple mail() function (fallback)
if (!function_exists('sendEmailSimple')) {
    function sendEmailSimple($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>' . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
}

// ==========================================================
//  NEW FUNCTION: SEND FORMATTED SYSTEM EMAIL (ADDED HERE)
// ==========================================================
if (!function_exists('sendSystemEmail')) {
    function sendSystemEmail($to, $subject, $title, $body_content, $action_link = null, $action_text = "View Dashboard") {
        $link_html = "";
        if ($action_link) {
            $link_html = "
                <p style='text-align: center;'>
                    <a href='$action_link' style='display: inline-block; padding: 12px 25px; background: #354db6; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold;'>$action_text</a>
                </p>
                <p style='text-align: center; font-size: 12px; color: #666;'>Or copy this link: <br>$action_link</p>
            ";
        }

        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #354db6 0%, #1e2a91 100%); color: white; padding: 30px 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .footer { text-align: center; padding: 20px; background: #f4f4f4; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h2>$title</h2>
                </div>
                <div class='content'>
                    $body_content
                    $link_html
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return sendEmail($to, $subject, $message);
    }
}
// ==========================================================

// Get system settings
if (!function_exists('getSystemSettings')) {
    function getSystemSettings($conn) {
        static $settings = null;
        
        if ($settings === null) {
            $stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        
        return $settings;
    }
}

// Check if maintenance mode is enabled
if (!function_exists('checkMaintenanceMode')) {
    function checkMaintenanceMode($conn) {
        // Always start the session if not started yet
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $settings = getSystemSettings($conn);
        $maintenance_mode = isset($settings['maintenance_mode']) ? $settings['maintenance_mode'] : '0';
        
        // If maintenance mode is on AND user is not admin, redirect to maintenance page
        if ($maintenance_mode == '1') {
            $user_type = $_SESSION['user_type'] ?? '';
            
            // Allow admins to pass through
            if ($user_type !== 'admin') {
                // Don't redirect if already on maintenance page or login page
                $current_page = basename($_SERVER['PHP_SELF']);
                if ($current_page !== 'maintenance.php' && $current_page !== 'login.php') {
                    header("Location: " . SITE_URL . "/maintenance.php");
                    exit;
                }
            }
        }
    }
}

// Check if registration is allowed
if (!function_exists('isRegistrationAllowed')) {
    function isRegistrationAllowed($conn) {
        $settings = getSystemSettings($conn);
        return isset($settings['allow_registration']) && $settings['allow_registration'] == '1';
    }
}

// Check if student has reached daily request limit
if (!function_exists('checkRequestLimit')) {
    function checkRequestLimit($conn, $student_id) {
        $settings = getSystemSettings($conn);
        $max_requests = isset($settings['max_requests_per_day']) ? (int)$settings['max_requests_per_day'] : 5;
        
        // Count today's requests
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM document_requests 
            WHERE student_id = ? 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$student_id]);
        $today_count = $stmt->fetch()['count'];
        
        return [
            'limit_reached' => ($today_count >= $max_requests),
            'count' => $today_count,
            'max' => $max_requests,
            'remaining' => max(0, $max_requests - $today_count)
        ];
    }
}

if (!function_exists('checkAuth')) {
    function checkAuth(array $allowed_roles = [])
    {
        global $conn;
        
        // Always start the session if not started yet
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Prevent redirect loops
        if (headers_sent()) {
            return;
        }

        // If no user logged in → redirect
        if (empty($_SESSION['user_id']) || empty($_SESSION['user_type'])) {
            // Use absolute path from SITE_URL constant
            header("Location: " . SITE_URL . "/login.php");
            exit;
        }

        // If allowed_roles was passed, check role
        if (!empty($allowed_roles)) {
            $user_type = $_SESSION['user_type'];
            
            if (!in_array($user_type, $allowed_roles)) {
                // Use absolute path from SITE_URL constant instead of calculating depth
                $redirect_url = SITE_URL . '/';
                
                // Redirect to appropriate dashboard based on their actual role
                switch($user_type) {
                    case 'student':
                        header("Location: {$redirect_url}student/dashboard.php");
                        exit;
                    case 'admin':
                        header("Location: {$redirect_url}admin/dashboard.php");
                        exit;
                    case 'registrar':
                        header("Location: {$redirect_url}department/registrar/dashboard.php");
                        exit;
                    case 'cashier':
                        header("Location: {$redirect_url}department/cashier/dashboard.php");
                        exit;
                    case 'frontline':
                        header("Location: {$redirect_url}department/frontline/dashboard.php");
                        exit;
                    case 'osas':
                        header("Location: {$redirect_url}department/osas/dashboard.php");
                        exit;
                    case 'library':
                        header("Location: {$redirect_url}department/library/dashboard.php");
                        exit;
                    case 'faculty':
                        header("Location: {$redirect_url}department/faculty/dashboard.php");
                        exit;
                    case 'property':
                        header("Location: {$redirect_url}department/property/dashboard.php");
                        exit;
                    case 'dean':
                        header("Location: {$redirect_url}department/dean/dashboard.php");
                        exit;
                    default:
                        // Unknown role, log them out
                        session_destroy();
                        header("Location: {$redirect_url}login.php");
                        exit;
                }
            }
        }
        
        // Check maintenance mode AFTER authentication (so admins can still access)
        checkMaintenanceMode($conn);
    }
}

// Activity logging function
if (!function_exists('logActivity')) {
    function logActivity($conn, $user_id, $action, $details) {
        try {
            // Get client IP address safely
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

            // Insert into your system_logs table
            $stmt = $conn->prepare("
                INSERT INTO system_logs (user_id, action, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $action, $details, $ip]);
        } catch (Exception $e) {
            error_log('logActivity() failed: ' . $e->getMessage());
        }
    }
}

// Alias for logActivity
if (!function_exists('logAction')) {
    function logAction($conn, $user_id, $action, $details) {
        return logActivity($conn, $user_id, $action, $details);
    }
}

// ============================================
// NEW DEPARTMENT HELPER FUNCTIONS
// ============================================

// Redirect helper for different user types
if (!function_exists('redirectToDashboard')) {
    function redirectToDashboard($user_type) {
        $dashboards = [
            'admin' => 'admin/dashboard.php',
            'student' => 'student/dashboard.php',
            'registrar' => 'department/registrar/dashboard.php',
            'cashier' => 'department/cashier/dashboard.php',
            'frontline' => 'department/frontline/dashboard.php',
            'osas' => 'department/osas/dashboard.php',
            'library' => 'department/library/dashboard.php',
            'faculty' => 'department/faculty/dashboard.php',
            'property' => 'department/property/dashboard.php',
            'dean' => 'department/dean/dashboard.php'
        ];
        
        $dashboard = $dashboards[$user_type] ?? 'index.php';
        header("Location: $dashboard");
        exit();
    }
}

// Get user's assigned college (for college-specific departments)
if (!function_exists('getUserCollege')) {
    function getUserCollege($conn, $user_id) {
        $stmt = $conn->prepare("SELECT dh.college_department 
            FROM department_handlers dh 
            WHERE dh.user_id = ? AND dh.is_active = 1 
            LIMIT 1");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['college_department'] : null;
    }
}

// Check if user can approve for a specific student
if (!function_exists('canApproveForStudent')) {
    function canApproveForStudent($conn, $user_id, $user_type, $student_department) {
        // OSAS and Library can approve for all students (universal)
        if (in_array($user_type, ['osas', 'library', 'registrar', 'cashier', 'frontline'])) {
            return true;
        }
        
        // Faculty, Property, Dean must match college
        if (in_array($user_type, ['faculty', 'property', 'dean'])) {
            $assigned_college = getUserCollege($conn, $user_id);
            return $assigned_college === $student_department;
        }
        
        return false;
    }
}

// Get signatory name based on user type
if (!function_exists('getSignatoryName')) {
    function getSignatoryName($user_type) {
        $signatories = [
            'registrar' => 'Registrar/Reg Staff',
            'faculty' => 'Faculty Adviser',
            'osas' => 'OSAS/Guidance Director',
            'library' => 'Librarian',
            'property' => 'Property Custodian',
            'dean' => 'Dean/Principal'
        ];
        
        return $signatories[$user_type] ?? '';
    }
}

// Get department display name
if (!function_exists('getDepartmentDisplayName')) {
    function getDepartmentDisplayName($user_type) {
        $names = [
            'registrar' => 'Registrar Office',
            'cashier' => 'Cashier Office',
            'frontline' => 'Frontline Services',
            'osas' => 'OSAS/Guidance Office',
            'library' => 'Library',
            'faculty' => 'Faculty Adviser',
            'property' => 'Property Custodian',
            'dean' => 'Dean/Principal Office'
        ];
        
        return $names[$user_type] ?? 'Department';
    }
}

// Check if department is college-specific
if (!function_exists('isCollegeSpecificDepartment')) {
    function isCollegeSpecificDepartment($user_type) {
        return in_array($user_type, ['faculty', 'property', 'dean']);
    }
}

// Check if department is universal (handles all students)
if (!function_exists('isUniversalDepartment')) {
    function isUniversalDepartment($user_type) {
        return in_array($user_type, ['osas', 'library', 'registrar', 'cashier', 'frontline']);
    }
}

// Get department icon based on user type
if (!function_exists('getDepartmentIcon')) {
    function getDepartmentIcon($user_type) {
        $icons = [
            'registrar' => 'bi-file-earmark-text',
            'cashier' => 'bi-cash-coin',
            'frontline' => 'bi-person-check',
            'osas' => 'bi-people',
            'library' => 'bi-book',
            'faculty' => 'bi-person-workspace',
            'property' => 'bi-box-seam',
            'dean' => 'bi-person-badge-fill'
        ];
        
        return $icons[$user_type] ?? 'bi-building';
    }
}

// Get department color class
if (!function_exists('getDepartmentColor')) {
    function getDepartmentColor($user_type) {
        $colors = [
            'registrar' => 'primary',
            'cashier' => 'success',
            'frontline' => 'info',
            'osas' => 'warning',
            'library' => 'secondary',
            'faculty' => 'purple',
            'property' => 'orange',
            'dean' => 'danger'
        ];
        
        return $colors[$user_type] ?? 'dark';
    }
}
?>