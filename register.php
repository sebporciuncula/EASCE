<?php 
require_once 'config.php';
session_start();

$error = '';
$success = '';
$show_otp = false;

if (!isRegistrationAllowed($conn)) {
    header("Location: login.php?error=Registration is currently disabled");
    exit;
}

// Step 1: Initial Registration & Send OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $student_id = trim($_POST['student_id']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $department = trim($_POST['department']);
    $program = trim($_POST['program']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $privacy_accepted = isset($_POST['privacy_accepted']) ? true : false;
    
    // Validation
    if (empty($student_id) || empty($fullname) || empty($email) || empty($contact_number) || empty($department) || empty($program) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!$privacy_accepted) {
        $error = "You must accept the Data Privacy Policy to register.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
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
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "This email is already registered.";
        } else {
            // Check if fullname already exists
            $stmt = $conn->prepare("SELECT id FROM student_profiles WHERE LOWER(fullname) = LOWER(?)");
            $stmt->execute([$fullname]);
            
            if ($stmt->rowCount() > 0) {
                $error = "This name is already registered in the system. Please use your full legal name.";
            } else {
                // VERIFY AGAINST MASTERLIST
                $stmt = $conn->prepare("
                    SELECT * FROM masterlist_students 
                    WHERE student_id = ? 
                    AND LOWER(TRIM(fullname)) = LOWER(TRIM(?))
                    AND department = ?
                    AND program = ?
                    AND status = 'active'
                ");
                $stmt->execute([$student_id, $fullname, $department, $program]);
                $masterlist_student = $stmt->fetch();
                
                if (!$masterlist_student) {
                    $error = "Student verification failed. Your information does not match our masterlist records. Please verify your Student ID, Full Name, Department, and Program. Contact the registrar's office if you believe this is an error.";
                } else {
                    // Generate OTP
                    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Store registration data in session
                    $_SESSION['registration_data'] = [
                        'student_id' => $student_id,
                        'fullname' => $masterlist_student['fullname'],
                        'email' => $email,
                        'contact_number' => $contact_number,
                        'department' => $masterlist_student['department'],
                        'program' => $masterlist_student['program'],
                        'year_level' => $masterlist_student['year_level'],
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'otp' => $otp,
                        'otp_expires' => $otp_expires
                    ];
                    
                    // Send OTP Email
                    $subject = "Email Verification - EAS-CE Registration";
                    $message = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                                .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                                .header { background: #010d25; color: white; padding: 30px 20px; text-align: center; }
                                .content { padding: 30px; }
                                .otp-box { background: #f8f9fa; border: 2px dashed #010d25; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
                                .otp-code { font-size: 32px; font-weight: bold; color: #010d25; letter-spacing: 8px; margin: 10px 0; }
                                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
                                .footer { text-align: center; padding: 20px; background: #f4f4f4; color: #666; font-size: 12px; }
                            </style>
                        </head>
                        <body>
                            <div class='email-container'>
                                <div class='header'>
                                    <h2>🔐 Email Verification</h2>
                                </div>
                                <div class='content'>
                                    <p>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                                    <p>Thank you for registering with <strong>EAS-CE</strong>. To complete your registration, please verify your email address using the OTP code below:</p>
                                    
                                    <div class='otp-box'>
                                        <p style='margin: 0; color: #666; font-size: 14px;'>Your Verification Code</p>
                                        <div class='otp-code'>" . $otp . "</div>
                                        <p style='margin: 0; color: #666; font-size: 12px;'>Valid for 10 minutes</p>
                                    </div>
                                    
                                    <div class='warning'>
                                        <strong>⚠️ Security Notice:</strong>
                                        <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                                            <li>This code expires in <strong>10 minutes</strong></li>
                                            <li>Do not share this code with anyone</li>
                                            <li>If you didn't request this, please ignore this email</li>
                                        </ul>
                                    </div>
                                    
                                    <p><strong>Registration Details:</strong></p>
                                    <ul>
                                        <li>Student ID: " . htmlspecialchars($student_id) . "</li>
                                        <li>Email: " . htmlspecialchars($email) . "</li>
                                        <li>Department: " . htmlspecialchars($department) . "</li>
                                        <li>Program: " . htmlspecialchars($program) . "</li>
                                    </ul>
                                </div>
                                <div class='footer'>
                                    <p>&copy; " . date('Y') . " EAS-CE System. All rights reserved.</p>
                                    <p>This is an automated message, please do not reply.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    
                    if (sendEmail($email, $subject, $message)) {
                        $show_otp = true;
                        $success = "Verification code sent to your email! Please check your inbox.";
                    } else {
                        $error = "Failed to send verification email. Please try again.";
                        unset($_SESSION['registration_data']);
                    }
                }
            }
        }
    }
}

// Step 2: Verify OTP & Complete Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp']);
    
    if (!isset($_SESSION['registration_data'])) {
        $error = "Session expired. Please start registration again.";
    } elseif (empty($entered_otp)) {
        $error = "Please enter the verification code.";
        $show_otp = true;
    } else {
        $reg_data = $_SESSION['registration_data'];
        
        // Check if OTP expired
        if (strtotime($reg_data['otp_expires']) < time()) {
            $error = "Verification code has expired. Please request a new one.";
            unset($_SESSION['registration_data']);
        } elseif ($entered_otp !== $reg_data['otp']) {
            $error = "Invalid verification code. Please try again.";
            $show_otp = true;
        } else {
            // OTP is correct - Create account
            try {
                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, status) VALUES (?, ?, 'student', 'active')");
                $stmt->execute([$reg_data['email'], $reg_data['password']]);
                $user_id = $conn->lastInsertId();
                
                // Insert into student_profiles
                $stmt = $conn->prepare("
                    INSERT INTO student_profiles (user_id, fullname, department, program, student_id, year_level, contact_number) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id, 
                    $reg_data['fullname'],
                    $reg_data['department'], 
                    $reg_data['program'],
                    $reg_data['student_id'],
                    $reg_data['year_level'],
                    $reg_data['contact_number']
                ]);
                
                // Log the registration
                logActivity($conn, $user_id, 'User Registered', "Student registered and verified: {$reg_data['email']} (Student ID: {$reg_data['student_id']})");
                
                // Send welcome email
                $welcome_subject = "Welcome to EAS-CE!";
                $welcome_message = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                            .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                            .header { background: #010d25; color: white; padding: 30px 20px; text-align: center; }
                            .content { padding: 30px; }
                            .button { display: inline-block; padding: 12px 30px; background: #010d25; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                            .footer { text-align: center; padding: 20px; background: #f4f4f4; color: #666; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='header'>
                                <h2>🎉 Welcome to EAS-CE!</h2>
                            </div>
                            <div class='content'>
                                <p>Hello <strong>" . htmlspecialchars($reg_data['fullname']) . "</strong>,</p>
                                <p>Your registration has been successfully completed and your account is now active!</p>
                                <p><strong>Account Details:</strong></p>
                                <ul>
                                    <li>Student ID: " . htmlspecialchars($reg_data['student_id']) . "</li>
                                    <li>Email: " . htmlspecialchars($reg_data['email']) . "</li>
                                    <li>Department: " . htmlspecialchars($reg_data['department']) . "</li>
                                    <li>Program: " . htmlspecialchars($reg_data['program']) . "</li>
                                </ul>
                                <p>You can now log in and start using the system.</p>
                                <center>
                                    <a href='" . SITE_URL . "/login.php' class='button'>Login Now</a>
                                </center>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " EAS-CE System. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                sendEmail($reg_data['email'], $welcome_subject, $welcome_message);
                
                // Clear session
                unset($_SESSION['registration_data']);
                
                $success = "Registration successful! Your account has been verified and activated. You can now log in.";
                $show_otp = false;
                
            } catch (Exception $e) {
                $error = "Registration failed. Please try again.";
                $show_otp = true;
            }
        }
    }
}

// Resend OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_otp'])) {
    if (!isset($_SESSION['registration_data'])) {
        $error = "Session expired. Please start registration again.";
    } else {
        $reg_data = $_SESSION['registration_data'];
        
        // Generate new OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $_SESSION['registration_data']['otp'] = $otp;
        $_SESSION['registration_data']['otp_expires'] = $otp_expires;
        
        // Resend email
        $subject = "Email Verification - EAS-CE Registration";
        $message = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                    .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .header { background: #010d25; color: white; padding: 30px 20px; text-align: center; }
                    .content { padding: 30px; }
                    .otp-box { background: #f8f9fa; border: 2px dashed #010d25; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
                    .otp-code { font-size: 32px; font-weight: bold; color: #010d25; letter-spacing: 8px; margin: 10px 0; }
                    .footer { text-align: center; padding: 20px; background: #f4f4f4; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='header'>
                        <h2>🔐 New Verification Code</h2>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>Here is your new verification code:</p>
                        <div class='otp-box'>
                            <p style='margin: 0; color: #666; font-size: 14px;'>Your Verification Code</p>
                            <div class='otp-code'>" . $otp . "</div>
                            <p style='margin: 0; color: #666; font-size: 12px;'>Valid for 10 minutes</p>
                        </div>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " EAS-CE System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        if (sendEmail($reg_data['email'], $subject, $message)) {
            $success = "New verification code sent! Please check your email.";
            $show_otp = true;
        } else {
            $error = "Failed to resend code. Please try again.";
            $show_otp = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | EAS-CE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
     <!-- Your reusable responsive CSS -->
    <style>
        body {
            background: url('assets/landing-bg.png') center/cover no-repeat fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 0;
            margin: 0;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1;
        }
        .content-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 140px 20px 80px 20px;
        }
        .register-box {
            position: relative;
            z-index: 10;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px;
            width: 450px;
            max-width: 90%;
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
        .register-box h3 {
            text-align: center;
            font-weight: bold;
            margin-bottom: 25px;
            font-size: 26px;
            color: white;
        }
        .form-control, .form-select {
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: none;
            padding: 12px 20px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .form-control::placeholder { 
            color: #ddd; 
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.3) !important;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            border: none;
            transform: translateY(-2px);
        }
        .form-select {
            color: #fff;
            cursor: pointer;
        }
        .form-select option {
            background: rgba(1, 13, 37, 0.95);
            color: #fff;
        }
        .mb-3 {
            margin-bottom: 15px !important;
        }
        .btn-register, .btn-verify {
            width: 100%;
            border-radius: 30px;
            background-color: #007bff;
            border: none;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 15px;
            margin-top: 10px;
            color: white;
        }
        .btn-register:hover, .btn-verify:hover { 
            background-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(1, 13, 37, 0.4);
        }
        .btn-register:disabled, .btn-verify:disabled {
            background-color: rgba(108, 117, 125, 0.6);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-resend {
            width: 100%;
            border-radius: 30px;
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
            color: white;
            margin-top: 10px;
        }
        .btn-resend:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .error-msg, .success-msg {
            border-radius: 30px;
            padding: 12px 20px;
            text-align: center;
            margin-bottom: 20px;
            animation: shake 0.5s ease;
            font-size: 14px;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .error-msg {
            background: rgba(255, 0, 0, 0.3);
            color: #ffb3b3;
        }
        .success-msg {
            background: rgba(0, 255, 0, 0.2);
            color: #b3ffb3;
        }
        a { 
            color: #fff; 
            text-decoration: none;
            transition: all 0.3s ease;
        }
        a:hover {
            color: #007bff;
            text-decoration: underline;
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
        .info-box {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
            color: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .info-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #fff;
        }
        .info-box ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 4px 0;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .privacy-checkbox {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .privacy-checkbox .form-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .privacy-checkbox .form-check-input {
            margin-top: 4px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .privacy-checkbox .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        .privacy-checkbox .form-check-label {
            font-size: 13px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
        }
        .privacy-link {
            color: #66b3ff;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
        }
        .privacy-link:hover {
            color: #3399ff;
        }

        .password-requirements {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 12px;
            margin-top: 8px;
            font-size: 12px;
        }
        .password-requirements ul {
            margin: 5px 0 0 0;
            padding-left: 20px;
            list-style: none;
        }
        .password-requirements li {
            margin: 3px 0;
            color: rgba(255, 255, 255, 0.7);
        }
        .password-requirements li.valid {
            color: #4ade80;
        }
        .password-requirements li.valid::before {
            content: "✓ ";
            font-weight: bold;
        }
        .password-requirements li.invalid::before {
            content: "✗ ";
            font-weight: bold;
        }

        .otp-input-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .otp-digit {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        .otp-digit:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            transform: scale(1.05);
        }
        .timer {
            text-align: center;
            margin: 15px 0;
            font-size: 14px;
            color: #ffc107;
        }
        .links-container {
            margin-top: 20px;
            text-align: center;
        }
        .links-container small {
            display: block;
            margin: 5px 0;
        }

        /* Modal Styles */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }
        .modal-content {
            border-radius: 25px;
            background: rgba(1, 13, 37, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 50px rgba(0,0,0,0.6);
            color: white;
        }
        .modal-header {
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 25px 25px 0 0;
            padding: 25px 30px;
        }
        .modal-title {
            color: white;
            font-weight: bold;
            font-size: 1.4rem;
        }
        .modal-body {
            max-height: 65vh;
            overflow-y: auto;
            padding: 35px;
        }
        .modal-body h5 {
            color: #66b3ff;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .modal-body h5:first-child {
            margin-top: 0;
        }
        .modal-body ul {
            padding-left: 20px;
        }
        .modal-body li {
            margin: 8px 0;
        }
        .modal-footer {
            border: none;
            padding: 25px 35px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 0 0 25px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-secondary {
            background: rgba(108, 117, 125, 0.5);
            border: none;
            border-radius: 20px;
            padding: 10px 25px;
        }
        .btn-secondary:hover {
            background: rgba(108, 117, 125, 0.7);
        }
        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 20px;
            padding: 10px 25px;
        }
        .btn-primary:hover {
            background: #0056b3;
        }

        /* Responsive styles */
        body {
            padding: 0;
            margin: 0;
        }

        .content-wrapper {
            padding: 140px 20px 80px 20px;
        }

        .register-box {
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }

        .register-box h3 {
            font-size: clamp(1.5rem, 5vw, 1.75rem);
        }

        .form-control, .form-select {
            font-size: clamp(0.875rem, 2vw, 0.95rem);
            padding: 12px 20px;
        }

        .btn-register, .btn-verify, .btn-resend {
            font-size: clamp(0.875rem, 2vw, 1rem);
            padding: 12px;
        }

        .info-box {
            font-size: clamp(0.75rem, 2vw, 0.875rem);
            padding: 15px;
        }

        .info-box strong {
            font-size: clamp(0.8rem, 2vw, 0.95rem);
        }

        .top-brand {
            top: 20px;
            left: 20px;
        }

        .otp-digit {
            width: clamp(40px, 10vw, 50px);
            height: clamp(50px, 12vw, 60px);
            font-size: clamp(18px, 5vw, 24px);
        }

        .timer {
            font-size: clamp(0.75rem, 2vw, 0.875rem);
        }

        .links-container small {
            font-size: clamp(0.75rem, 2vw, 0.875rem);
        }

        .password-requirements {
            font-size: clamp(0.65rem, 1.8vw, 0.75rem);
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 100px 15px 60px 15px;
            }
            
            .register-box {
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
            
            .otp-input-group {
                gap: 5px;
            }
            
            .mb-3 {
                margin-bottom: 12px !important;
            }
            
            .info-box {
                padding: 12px;
            }
            
            .privacy-checkbox {
                padding: 12px;
            }
            
            .modal-body {
                padding: 25px 20px;
            }
            
            .modal-header {
                padding: 20px;
            }
            
            .modal-title {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .content-wrapper {
                padding: 80px 10px 40px 10px;
            }
            
            .register-box {
                padding: 25px 15px;
                border-radius: 15px;
            }
            
            .top-brand {
                top: 10px;
                left: 10px;
            }
            
            .otp-digit {
                gap: 3px;
            }
            
            .modal-body {
                padding: 20px 15px;
                max-height: 60vh;
            }
        }

        /* Landscape orientation on mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .content-wrapper {
                padding: 80px 20px 40px 20px;
            }
            
            .register-box {
                padding: 20px;
            }
            
            .info-box {
                margin-bottom: 10px;
            }
            
            .mb-3 {
                margin-bottom: 8px !important;
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

    <div class="content-wrapper">
        <div class="register-box">
            <?php if (!$show_otp): ?>
                <!-- Registration Form -->
                <h3><i class="fas fa-user-plus"></i> Create Account</h3>

                <div class="info-box">
                    <i class="fas fa-info-circle"></i> <strong>Verification Required</strong>
                    <ul>
                        <li>Student ID</li>
                        <li>Full Name (exact spelling)</li>
                        <li>Valid Email Address</li>
                        <li>Department & Program</li>
                    </ul>
                </div>

                <?php if ($error && !$show_otp): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php elseif ($success && !$show_otp): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <div class="mb-3">
                        <input type="text" name="student_id" class="form-control" 
                               placeholder="Student ID (e.g., 2021-00001)" 
                               pattern="[0-9]{4}-[0-9]{5}" 
                               title="Format: YYYY-NNNNN (e.g., 2021-00001)"
                               required>
                    </div>

                    <div class="mb-3">
                        <input type="text" name="fullname" class="form-control" 
                               placeholder="Full Name (e.g., Mark James J. Rogelio)" required>
                    </div>

                    <div class="mb-3">
                        <select name="department" id="department" class="form-select" required>
                            <option value="">Select Department</option>
                            <option value="College of Health and Science">College of Health and Science</option>
                            <option value="College of Accountancy">College of Accountancy</option>
                            <option value="College of Art and Sciences">College of Art and Sciences</option>
                            <option value="College of Business and Administration">College of Business and Administration</option>
                            <option value="College of Computer Studies">College of Computer Studies</option>
                            <option value="College of Education">College of Education</option>
                            <option value="College of Hospitality Management and Tourism">College of Hospitality Management and Tourism</option>
                            <option value="College of Maritime Education">College of Maritime Education</option>
                            <option value="School of Mechanical Engineering">School of Mechanical Engineering</option>
                            <option value="School of Psychology">School of Psychology</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <select name="program" id="program" class="form-select" required>
                            <option value="">Select Program</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" 
                               placeholder="Email Address" required>
                    </div>

                    <div class="mb-3">
                        <input type="text" name="contact_number" class="form-control" 
                               placeholder="Contact Number (09123456789)" 
                               pattern="[0-9]{11}"
                               title="11-digit number"
                               required>
                    </div>

                    <div class="mb-3">
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Password (min 8 characters)" required minlength="8">
                        <div class="password-requirements" id="passwordRequirements">
                            <strong><i class="fas fa-key"></i> Password must contain:</strong>
                            <ul>
                                <li id="req-length" class="invalid">At least 8 characters</li>
                                <li id="req-uppercase" class="invalid">One uppercase letter (A-Z)</li>
                                <li id="req-lowercase" class="invalid">One lowercase letter (a-z)</li>
                                <li id="req-number" class="invalid">One number (0-9)</li>
                                <li id="req-special" class="invalid">One special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="confirm_password" class="form-control" 
                               placeholder="Confirm Password" required>
                    </div>

                    <div class="privacy-checkbox">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="privacy_accepted" id="privacyCheck" required>
                            <label class="form-check-label" for="privacyCheck">
                                I agree to the <span class="privacy-link" data-bs-toggle="modal" data-bs-target="#privacyModal">Data Privacy Policy</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn btn-register" id="registerBtn">
                        <i class="fas fa-user-check"></i> Register
                    </button>
                </form>

                <div class="links-container">
                    <small>Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Sign In</a></small>
                </div>

            <?php else: ?>
                <!-- OTP Verification Form -->
                <h3><i class="fas fa-shield-alt"></i> Verify Email</h3>

                <div class="info-box">
                    <i class="fas fa-envelope"></i> <strong>Check Your Email</strong>
                    <p style="margin: 8px 0 0 0;">We've sent a 6-digit verification code to:<br>
                    <strong><?= isset($_SESSION['registration_data']) ? htmlspecialchars($_SESSION['registration_data']['email']) : '' ?></strong></p>
                </div>

                <?php if ($error && $show_otp): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php elseif ($success && $show_otp): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="otpForm">
                    <div class="otp-input-group">
                        <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" required>
                    </div>
                    <input type="hidden" name="otp" id="otpValue">
                    
                    <div class="timer" id="timer">
                        <i class="fas fa-clock"></i> Code expires in: <span id="countdown">10:00</span>
                    </div>

                    <button type="submit" name="verify_otp" class="btn btn-verify">
                        <i class="fas fa-check-circle"></i> Verify & Complete Registration
                    </button>
                </form>

                <form method="POST">
                    <button type="submit" name="resend_otp" class="btn btn-resend" id="resendBtn">
                        <i class="fas fa-redo"></i> Resend Code
                    </button>
                </form>

                <div class="links-container">
                    <small><a href="register.php"><i class="fas fa-arrow-left"></i> Back to Registration</a></small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Data Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">
                        <i class="fas fa-shield-alt"></i> Data Privacy Policy
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5><i class="fas fa-info-circle"></i> Introduction</h5>
                    <p>The <strong>Easy Access Student Clearance and E-Document (EAS-CE)</strong> system is committed to protecting your personal data and respecting your privacy in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173).</p>
                    
                    <h5><i class="fas fa-database"></i> Information We Collect</h5>
                    <p>We collect the following personal information necessary for system operation:</p>
                    <ul>
                        <li><strong>Personal Identification:</strong> Student ID, Full Name, Email Address, Contact Number</li>
                        <li><strong>Academic Information:</strong> Department, Program, Year Level</li>
                        <li><strong>Document Requests:</strong> Types of documents requested, request dates, and purpose of requests</li>
                        <li><strong>System Usage Data:</strong> Login timestamps, request history, and transaction records</li>
                    </ul>

                    <h5><i class="fas fa-bullseye"></i> Purpose of Data Collection</h5>
                    <p>Your information is collected and processed for the following purposes:</p>
                    <ul>
                        <li>Account creation and identity verification</li>
                        <li>Processing clearance and document requests</li>
                        <li>Communication regarding request status and updates</li>
                        <li>Maintaining academic records and institutional compliance</li>
                        <li>Improving system performance and user experience</li>
                    </ul>

                    <h5><i class="fas fa-lock"></i> Data Security</h5>
                    <p>We implement appropriate technical and organizational security measures to protect your personal data, including:</p>
                    <ul>
                        <li>Encrypted password storage using industry-standard algorithms</li>
                        <li>Secure SSL/TLS connections for data transmission</li>
                        <li>Role-based access controls limiting data access to authorized personnel only</li>
                        <li>Regular system backups and security audits</li>
                        <li>Secure storage infrastructure with restricted physical and digital access</li>
                    </ul>

                    <h5><i class="fas fa-share-alt"></i> Data Sharing and Disclosure</h5>
                    <p>Your personal information will only be shared with:</p>
                    <ul>
                        <li>Authorized university departments necessary for processing your requests</li>
                        <li>External parties only when required by law or with your explicit consent</li>
                    </ul>
                    <p>We <strong>do not sell, rent, or trade</strong> your personal information to third parties.</p>

                    <h5><i class="fas fa-clock"></i> Data Retention</h5>
                    <p>Your personal data will be retained for as long as your account is active and for a period consistent with university policies and legal requirements after account closure or graduation.</p>

                    <h5><i class="fas fa-user-shield"></i> Your Rights</h5>
                    <p>Under the Data Privacy Act, you have the following rights:</p>
                    <ul>
                        <li><strong>Right to be Informed:</strong> You have the right to know how your data is collected, used, and shared</li>
                        <li><strong>Right to Access:</strong> You may request access to your personal data held by the system</li>
                        <li><strong>Right to Correction:</strong> You may request correction of inaccurate or incomplete data</li>
                        <li><strong>Right to Erasure:</strong> You may request deletion of your data under certain circumstances</li>
                        <li><strong>Right to Object:</strong> You may object to processing of your data for specific purposes</li>
                        <li><strong>Right to Data Portability:</strong> You may request a copy of your data in a structured format</li>
                    </ul>

                    <h5><i class="fas fa-phone"></i> Contact Information</h5>
                    <p>For questions, concerns, or to exercise your data privacy rights, please contact:</p>
                    <ul>
                        <li><strong>Data Protection Officer:</strong> Dr. Yanga's Colleges, Inc.</li>
                        <li><strong>Email:</strong> dpo@dyci.edu.ph</li>
                        <li><strong>Office:</strong> Registrar's Office, DYCI Elida Campus</li>
                    </ul>

                    <h5><i class="fas fa-edit"></i> Changes to this Policy</h5>
                    <p>We may update this Data Privacy Policy from time to time. Users will be notified of significant changes through email or system announcements.</p>

                    <hr style="border-color: rgba(255,255,255,0.2); margin: 25px 0;">
                    <p class="text-muted small" style="color: rgba(255,255,255,0.7) !important;">
                        <em><strong>Consent:</strong> By checking the box and proceeding with registration, you acknowledge that you have read, understood, and agree to this Data Privacy Policy. You consent to the collection, processing, and storage of your personal information as described above.</em>
                    </p>
                    <p class="text-muted small" style="color: rgba(255,255,255,0.7) !important;">
                        <em>Last Updated: <?= date('F Y') ?></em>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="acceptPrivacy()">
                        <i class="fas fa-check"></i> I Understand & Accept
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Program selection
        const programsByDept = {
            "College of Health and Science": ["BS in Nursing"],
            "College of Accountancy": ["BS in Accountancy"],
            "College of Art and Sciences": ["BA in Communication", "BS in Political Science"],
            "College of Business and Administration": ["BS in Business Administration (Marketing, HRM, Financial Management)"],
            "College of Computer Studies": ["BS in Computer Science", "BS in Information Technology", "BS in Computer Engineering"],
            "College of Education": ["Bachelor of Elementary Education", "Bachelor of Secondary Education (Math, Science, English)"],
            "College of Hospitality Management and Tourism": ["BS in Hospitality Management", "BS in Tourism Management"],
            "College of Maritime Education": ["BS in Marine Transportation", "BS in Marine Engineering"],
            "School of Mechanical Engineering": ["BS in Mechanical Engineering"],
            "School of Psychology": ["BS in Psychology"]
        };

        const deptSelect = document.getElementById('department');
        const programSelect = document.getElementById('program');

        if (deptSelect) {
            deptSelect.addEventListener('change', function() {
                const dept = this.value;
                programSelect.innerHTML = '<option value="">Select Program</option>';
                
                if (programsByDept[dept]) {
                    programsByDept[dept].forEach(prog => {
                        const opt = document.createElement('option');
                        opt.value = prog;
                        opt.textContent = prog;
                        programSelect.appendChild(opt);
                    });
                }
            });
        }

        // Password Requirements Validation
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                // Length check
                const lengthValid = password.length >= 8;
                document.getElementById('req-length').className = lengthValid ? 'valid' : 'invalid';
                
                // Uppercase check
                const uppercaseValid = /[A-Z]/.test(password);
                document.getElementById('req-uppercase').className = uppercaseValid ? 'valid' : 'invalid';
                
                // Lowercase check
                const lowercaseValid = /[a-z]/.test(password);
                document.getElementById('req-lowercase').className = lowercaseValid ? 'valid' : 'invalid';
                
                // Number check
                const numberValid = /[0-9]/.test(password);
                document.getElementById('req-number').className = numberValid ? 'valid' : 'invalid';
                
                // Special character check
                const specialValid = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                document.getElementById('req-special').className = specialValid ? 'valid' : 'invalid';
            });
        }

        function acceptPrivacy() {
            document.getElementById('privacyCheck').checked = true;
        }

        const privacyCheck = document.getElementById('privacyCheck');
        const registerBtn = document.getElementById('registerBtn');

        if (privacyCheck && registerBtn) {
            privacyCheck.addEventListener('change', function() {
                registerBtn.disabled = !this.checked;
            });
            registerBtn.disabled = true;
        }

        // OTP Input Handling
        const otpInputs = document.querySelectorAll('.otp-digit');
        const otpForm = document.getElementById('otpForm');
        const otpValue = document.getElementById('otpValue');

        if (otpInputs.length > 0) {
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const value = this.value;
                    
                    // Only allow numbers
                    if (!/^\d$/.test(value)) {
                        this.value = '';
                        return;
                    }

                    // Move to next input
                    if (value && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }

                    // Update hidden input
                    updateOTPValue();
                });

                input.addEventListener('keydown', function(e) {
                    // Handle backspace
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        otpInputs[index - 1].focus();
                        otpInputs[index - 1].value = '';
                        updateOTPValue();
                    }

                    // Handle arrow keys
                    if (e.key === 'ArrowLeft' && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                    if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                });

                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').trim();
                    
                    if (/^\d{6}$/.test(pastedData)) {
                        pastedData.split('').forEach((char, i) => {
                            if (otpInputs[i]) {
                                otpInputs[i].value = char;
                            }
                        });
                        otpInputs[5].focus();
                        updateOTPValue();
                    }
                });
            });

            function updateOTPValue() {
                const otp = Array.from(otpInputs).map(input => input.value).join('');
                if (otpValue) {
                    otpValue.value = otp;
                }
            }

            // Focus first input on load
            otpInputs[0].focus();

            // Countdown Timer
            let timeLeft = 600; // 10 minutes in seconds
            const countdownEl = document.getElementById('countdown');
            const resendBtn = document.getElementById('resendBtn');

            const timer = setInterval(() => {
                timeLeft--;
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                if (countdownEl) {
                    countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }

                if (timeLeft <= 0) {
                    clearInterval(timer);
                    if (countdownEl) {
                        countdownEl.textContent = 'Expired';
                        countdownEl.parentElement.style.color = '#ff6b6b';
                    }
                    if (resendBtn) {
                        resendBtn.disabled = false;
                    }
                }
            }, 1000);
        }
    </script>
</body>
</html>