<?php
require_once 'config.php';
date_default_timezone_set('Asia/Manila');

$error = '';
$success = '';
$valid_token = false;
$token = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    // Verify token
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $valid_token = true;
    } else {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = "Password must contain at least one special character.";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user['id']])) {
            $success = "Your password has been reset successfully. You can now login with your new password.";
            $valid_token = false;
            
            // Log the password reset
            logActivity($conn, $user['id'], 'Password Reset', 'User successfully reset their password');
            
            // Send confirmation email
            $subject = "Password Changed - " . SITE_NAME;
            $message = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                        .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px 20px; text-align: center; }
                        .content { padding: 30px; }
                        .footer { text-align: center; padding: 20px; background: #f4f4f4; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='header'>
                            <h2>✅ Password Successfully Changed</h2>
                        </div>
                        <div class='content'>
                            <p>Hello,</p>
                            <p>Your password for your <strong>EAS-CE</strong> account has been successfully changed.</p>
                            <p><strong>Details:</strong></p>
                            <ul>
                                <li>Date & Time: " . date('F j, Y g:i A') . "</li>
                                <li>IP Address: " . $_SERVER['REMOTE_ADDR'] . "</li>
                            </ul>
                            <p>If you did not make this change, please contact support immediately.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " EAS-CE System. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            sendEmail($user['email'], $subject, $message);
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - EAS-CE</title>
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
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1;
        }
        .reset-box {
            position: relative;
            z-index: 10;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            color: white;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .top-brand {
            position: absolute;
            top: 40px;
            left: 40px;
            display: flex;
            align-items: center;
            color: white;
            z-index: 10;
            animation: fadeIn 0.8s ease;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .top-brand:hover {
            transform: translateX(5px);
            color: white;
        }
        .top-brand:hover img {
            transform: scale(1.05);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .top-brand img {
            width: 100px;
            height: 100px;
            margin-right: 12px;
            object-fit: contain;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .brand-text h5 {
            margin: 0;
            font-weight: bold;
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            line-height: 1.2;
        }
        .brand-text small {
            font-size: clamp(0.65rem, 2vw, 0.75rem);
            opacity: 0.9;
            display: block;
            margin-top: 2px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container i {
            font-size: clamp(45px, 10vw, 60px);
            color: #909090ff;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        h2 {
            text-align: center;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: clamp(1.5rem, 5vw, 2rem);
        }
        .subtitle {
            text-align: center;
            color: #ddd;
            font-size: clamp(0.8rem, 2vw, 0.95rem);
            margin-bottom: 30px;
        }
        .form-control {
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: none;
            padding: 12px 20px;
            transition: all 0.3s ease;
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        .form-control::placeholder {
            color: #ddd;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.3) !important;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            border: none;
            transform: translateY(-2px);
        }
        .btn-primary {
            width: 100%;
            border-radius: 30px;
            background-color: #007bff;
            border: none;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }
        .btn-primary:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        .back-link {
            color: #e7e7e7ff;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: clamp(0.8rem, 2vw, 0.95rem);
        }
        .back-link:hover {
            color: #ffffffff;
            text-decoration: underline;
        }
        .alert {
            border-radius: 15px;
            border: none;
            animation: slideDown 0.5s ease;
            font-size: clamp(0.75rem, 2vw, 0.875rem);
            padding: 12px 15px;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .alert-danger {
            background: rgba(220, 53, 69, 0.3);
            color: #ffb3b3;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.3);
            color: #b3ffb3;
        }
        .alert-warning {
            background: rgba(255, 193, 7, 0.3);
            color: #fff3cd;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 8px;
            transition: all 0.3s;
            width: 0%;
            background: rgba(255,255,255,0.3);
        }
        .password-requirements {
            font-size: clamp(0.65rem, 1.8vw, 0.75rem);
            color: #ddd;
            margin-top: 8px;
            padding: 10px;
        }
        .requirement {
            display: block;
            margin: 3px 0;
            opacity: 0.6;
            font-size: clamp(0.65rem, 1.8vw, 0.7rem);
        }
        .requirement.met {
            color: #90EE90;
            opacity: 1;
        }
        .requirement i {
            font-size: 8px;
        }
        #matchStatus {
            display: block;
            margin-top: 5px;
            font-size: clamp(0.7rem, 2vw, 0.8rem);
        }
        .input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #ddd;
            transition: color 0.3s;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        .toggle-password:hover {
            color: #fff;
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .reset-box {
                padding: 30px 20px;
                max-width: 95%;
            }
            
            .top-brand {
                top: 15px;
                left: 15px;
            }
            
            .brand-text small {
                display: none;
            }
            
            .logo-container {
                margin-bottom: 20px;
            }
            
            .subtitle {
                margin-bottom: 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .reset-box {
                padding: 25px 15px;
                border-radius: 15px;
            }
            
            .top-brand {
                top: 10px;
                left: 10px;
            }
            
            h2 {
                margin-bottom: 8px;
            }
            
            .subtitle {
                margin-bottom: 20px;
            }
            
            .mb-3 {
                margin-bottom: 12px !important;
            }
        }

        /* Landscape orientation on mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 10px;
                align-items: flex-start;
            }
            
            .reset-box {
                padding: 20px 15px;
                margin: 20px auto;
            }
            
            .logo-container {
                margin-bottom: 15px;
            }
            
            .subtitle {
                margin-bottom: 15px;
            }
            
            .mb-3 {
                margin-bottom: 10px !important;
            }
            
            .top-brand {
                top: 10px;
                left: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <a href="index.php" class="top-brand">
        <img src="assets/logo.png" alt="DYCI Logo">
        <div class="brand-text">
            <h5>EAS-CE</h5>
            <small>Easy Access Student Clearance & E-Documents</small>
        </div>
    </a>

    <div class="reset-box">
        <div class="logo-container">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h2>Reset Password</h2>
        <p class="subtitle">Enter your new password below.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success mb-3">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
        <?php elseif ($valid_token): ?>
            <form method="POST" action="" id="resetForm">
                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="password" class="form-control" name="password" 
                               id="password" placeholder="Enter new password" required minlength="8">
                        <i class="fas fa-eye toggle-password" id="toggleIcon1" onclick="togglePassword('password', 'toggleIcon1')"></i>
                    </div>
                    <div class="password-strength" id="strength"></div>
                    <div class="password-requirements">
                        <span class="requirement" id="req-length"><i class="fas fa-circle"></i> At least 8 characters</span>
                        <span class="requirement" id="req-upper"><i class="fas fa-circle"></i> One uppercase letter</span>
                        <span class="requirement" id="req-lower"><i class="fas fa-circle"></i> One lowercase letter</span>
                        <span class="requirement" id="req-number"><i class="fas fa-circle"></i> One number</span>
                        <span class="requirement" id="req-special"><i class="fas fa-circle"></i> One special character (!@#$%^&*)</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="password" class="form-control" name="confirm_password" 
                               id="confirm_password" placeholder="Confirm new password" required>
                        <i class="fas fa-eye toggle-password" id="toggleIcon2" onclick="togglePassword('confirm_password', 'toggleIcon2')"></i>
                    </div>
                    <small id="matchStatus"></small>
                </div>
                
                <button type="submit" class="btn btn-primary mb-3" id="submitBtn" disabled>
                    <i class="fas fa-check"></i> Reset Password
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle"></i> Invalid or expired reset link.
            </div>
            <div class="text-center mt-3">
                <a href="forgot-password.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Request New Link
                </a>
            </div>
        <?php endif; ?>
        
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        const passwordField = document.getElementById('password');
        const confirmField = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        passwordField?.addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('strength');
            
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            document.getElementById('req-length').classList.toggle('met', hasLength);
            document.getElementById('req-upper').classList.toggle('met', hasUpper);
            document.getElementById('req-lower').classList.toggle('met', hasLower);
            document.getElementById('req-number').classList.toggle('met', hasNumber);
            document.getElementById('req-special').classList.toggle('met', hasSpecial);
            
            let score = 0;
            if (hasLength) score++;
            if (password.length >= 10) score++;
            if (hasUpper && hasLower) score++;
            if (hasNumber) score++;
            if (hasSpecial) score++;
            
            strength.style.width = (score * 20) + '%';
            
            if (score <= 2) {
                strength.style.backgroundColor = '#dc3545';
            } else if (score <= 3) {
                strength.style.backgroundColor = '#ffc107';
            } else {
                strength.style.backgroundColor = '#28a745';
            }
            
            checkPasswordMatch();
        });

        confirmField?.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const password = passwordField.value;
            const confirm = confirmField.value;
            const matchStatus = document.getElementById('matchStatus');
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    matchStatus.textContent = '✓ Passwords match';
                    matchStatus.style.color = '#90EE90';
                    
                    // Check all requirements
                    const hasLength = password.length >= 8;
                    const hasUpper = /[A-Z]/.test(password);
                    const hasLower = /[a-z]/.test(password);
                    const hasNumber = /\d/.test(password);
                    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                    
                    submitBtn.disabled = !(hasLength && hasUpper && hasLower && hasNumber && hasSpecial);
                } else {
                    matchStatus.textContent = '✗ Passwords do not match';
                    matchStatus.style.color = '#ffb3b3';
                    submitBtn.disabled = true;
                }
            } else {
                matchStatus.textContent = '';
                submitBtn.disabled = true;
            }
        }

        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = passwordField.value;
            const confirm = confirmField.value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            if (!/[A-Z]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter!');
                return false;
            }
            
            if (!/[a-z]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one lowercase letter!');
                return false;
            }
            
            if (!/\d/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one number!');
                return false;
            }
            
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one special character!');
                return false;
            }
        });
    </script>
</body>
</html>