<?php
// forgot-password.php
require_once 'config.php';
date_default_timezone_set('Asia/Manila');

// Siguraduhing walang naka-login session para ma-access ito
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/index.php"); // O kung saan ang dashboard mo
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_email = sanitize($_POST['email']);
    
    if (empty($input_email)) {
        $error = "Please enter your email address.";
    } else {
        // UPDATED SQL: Hanapin ang user gamit ang Primary Email OR Verified Recovery Email
        $stmt = $conn->prepare("
            SELECT id, email, recovery_email, recovery_email_verified 
            FROM users 
            WHERE (email = ? OR (recovery_email = ? AND recovery_email_verified = 1)) 
            AND status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([$input_email, $input_email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($stmt->execute([$token, $expires, $user['id']])) {
            
                // Build reset link
                $reset_link = SITE_URL . "/reset-password.php?token=" . $token;
                $subject = "Password Reset Request - " . SITE_NAME;
                
                // HTML Email Content
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
                            .button { display: inline-block; padding: 15px 30px; background: #354db6; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                            .link-box { background: #f9f9f9; padding: 15px; border-radius: 5px; word-break: break-all; margin: 20px 0; border-left: 4px solid #354db6; font-size: 12px; color: #555; }
                            .footer { text-align: center; padding: 20px; background: #f4f4f4; color: #666; font-size: 12px; }
                            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; font-size: 14px; }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='header'>
                                <h2>🔐 Password Reset</h2>
                            </div>
                            <div class='content'>
                                <p>Hello,</p>
                                <p>We received a request to reset the password for your <strong>EAS-CE</strong> account.</p>
                                <p style='text-align: center;'>
                                    <a href='$reset_link' class='button'>Reset My Password</a>
                                </p>
                                <div class='warning'>
                                    <strong>⏰ Note:</strong> This link expires in 1 hour.
                                </div>
                                <p>Or copy this link:</p>
                                <div class='link-box'>$reset_link</div>
                                <p>If you didn't ask for this, you can ignore this email.</p>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " " . SITE_NAME . "</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                $emailsSentTo = [];

                // 1. Send to Primary Email
                if (sendEmail($user['email'], $subject, $message)) {
                    $emailsSentTo[] = $user['email'];
                } else {
                    error_log("Failed to send reset email to primary: " . $user['email']);
                }

                // 2. Send to Verified Recovery Email (Updated Check)
                // Check if not empty AND explicitly verified
                if (!empty($user['recovery_email']) && $user['recovery_email_verified'] == 1) {
                    // Avoid sending twice if primary and recovery are same (rare but possible)
                    if ($user['recovery_email'] !== $user['email']) {
                        if (sendEmail($user['recovery_email'], $subject, $message)) {
                            $emailsSentTo[] = $user['recovery_email'];
                        } else {
                            error_log("Failed to send reset email to recovery: " . $user['recovery_email']);
                        }
                    }
                }

                // Feedback to User
                if (count($emailsSentTo) > 0) {
                    $maskedEmails = array_map(function($e) {
                        // Mask email for privacy (e.g., j***@gmail.com)
                        $parts = explode('@', $e);
                        return substr($parts[0], 0, 2) . '***@' . $parts[1];
                    }, $emailsSentTo);
                    
                    $success = "Password reset link sent to: <strong>" . implode(', ', $maskedEmails) . "</strong>. Please check your inbox and spam folder.";
                } else {
                    $error = "System error: Could not send email. Please verify your SMTP settings.";
                }
            } else {
                $error = "Database error. Please try again.";
            }
        } else {
            // Security: Don't reveal if account exists, but for now show success to avoid confusion
            $success = "If an active account exists for that email, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - EAS-CE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url('assets/landing-bg.png') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index: 1;
        }
        .forgot-box {
            position: relative; z-index: 10;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            color: white;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }
        @keyframes slideUp { from {opacity: 0; transform: translateY(30px);} to {opacity: 1; transform: translateY(0);} }
        .top-brand {
            position: fixed; top: 20px; left: 20px; display: flex; align-items: center;
            color: white; z-index: 10; text-decoration: none; transition: all 0.3s ease;
        }
        .top-brand:hover { transform: translateX(5px); color: white; }
        .top-brand img { width: 50px; height: 50px; margin-right: 12px; border-radius: 50%; }
        .brand-text h5 { margin: 0; font-weight: bold; font-size: 1.2rem; }
        .brand-text small { font-size: 0.75rem; opacity: 0.9; }
        .logo-container { text-align: center; margin-bottom: 20px; }
        .logo-container i { font-size: 50px; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        h2 { text-align: center; font-weight: 700; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #e0e0e0; font-size: 0.95rem; margin-bottom: 30px; }
        .form-control {
            border-radius: 30px; background: rgba(255, 255, 255, 0.2);
            color: #fff; border: 1px solid rgba(255,255,255,0.1); padding: 12px 20px;
        }
        .form-control::placeholder { color: #ddd; }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.3); color: #fff;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1); outline: none; border-color: white;
        }
        .btn-primary {
            width: 100%; border-radius: 30px; background-color: #0d6efd; border: none;
            padding: 12px; font-weight: bold; transition: all 0.3s ease;
        }
        .btn-primary:hover { background-color: #0b5ed7; transform: translateY(-2px); }
        .back-link { color: #fff; text-decoration: none; font-size: 0.9rem; opacity: 0.8; transition: 0.3s; }
        .back-link:hover { opacity: 1; text-decoration: underline; }
        .alert { border-radius: 10px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <a href="index.php" class="top-brand">
        <img src="assets/logo.png" alt="DYCI Logo">
        <div class="brand-text">
            <h5>EAS-CE</h5>
            <small>Student Clearance System</small>
        </div>
    </a>

    <div class="forgot-box">
        <div class="logo-container">
            <i class="fas fa-lock-open"></i>
        </div>
        
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your <b>Student Email</b> or verified <b>Recovery Email</b> to verify your account.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3 d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i> <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success mb-3">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <input type="email" class="form-control" name="email" 
                       placeholder="example@gmail.com" required autocomplete="email">
            </div>
            
            <button type="submit" class="btn btn-primary mb-4">
                Send Reset Link <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>
        
        <div class="text-center">
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>