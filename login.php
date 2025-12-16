<?php
session_start();
require_once 'config.php';

// ✅ If already logged in, redirect based on user type
if (isset($_SESSION['user_id']) && !isset($_SESSION['login_success'])) {
    switch (strtolower($_SESSION['user_type'])) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'registrar':
            header("Location: department/registrar/dashboard.php");
            break;
        case 'cashier':
            header("Location: department/cashier/dashboard.php");
            break;
        case 'frontline':
            header("Location: department/frontline/dashboard.php");
            break;
        case 'osas':
            header("Location: department/osas/dashboard.php");
            break;
        case 'library':
            header("Location: department/library/dashboard.php");
            break;
        case 'faculty':
            header("Location: department/faculty/dashboard.php");
            break;
        case 'property':
            header("Location: department/property/dashboard.php");
            break;
        case 'dean':
            header("Location: department/dean/dashboard.php");
            break;
        case 'student':
            header("Location: student/dashboard.php");
            break;
        default:
            header("Location: student/dashboard.php");
            break;
    }
    exit;
}

$error = '';

// ✅ Rate limiting configuration
$max_attempts = 5;
$lockout_time = 900;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT failed_attempts, last_failed_attempt FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user_check = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_check) {
            $failed_attempts = $user_check['failed_attempts'] ?? 0;
            $last_failed = $user_check['last_failed_attempt'];
            
            if ($failed_attempts >= $max_attempts) {
                $time_since_last_attempt = time() - strtotime($last_failed);
                
                if ($time_since_last_attempt < $lockout_time) {
                    $remaining_time = ceil(($lockout_time - $time_since_last_attempt) / 60);
                    $error = "Too many failed attempts. Please try again in {$remaining_time} minute(s).";
                } else {
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE email = ?");
                    $stmt->execute([$email]);
                }
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is not active. Please contact the administrator.";
                } else {
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE email = ?");
                    $stmt->execute([$email]);

                    // ✅ REMEMBER ME LOGIC
                    if (isset($_POST['remember'])) {
                        // Set cookie for 30 days
                        setcookie("remember_email", $email, time() + (86400 * 30), "/");
                    } else {
                        // Clear cookie if unchecked
                        if (isset($_COOKIE["remember_email"])) {
                            setcookie("remember_email", "", time() - 3600, "/");
                        }
                    }

                    $user_name = $user['email'];
                    
                    if ($user['user_type'] === 'student') {
                        $stmt = $conn->prepare("SELECT fullname FROM student_profiles WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($profile) $user_name = $profile['fullname'];
                    } else {
                        $stmt = $conn->prepare("SELECT fullname FROM staff_profiles WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($profile) $user_name = $profile['fullname'];
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = strtolower($user['user_type']);
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_name'] = $user_name;
                    $_SESSION['login_success'] = true;

                    switch ($_SESSION['user_type']) {
                        case 'admin': $redirect_url = "admin/dashboard.php"; break;
                        case 'registrar': $redirect_url = "department/registrar/dashboard.php"; break;
                        case 'cashier': $redirect_url = "department/cashier/dashboard.php"; break;
                        case 'frontline': $redirect_url = "department/frontline/dashboard.php"; break;
                        case 'osas': $redirect_url = "department/osas/dashboard.php"; break;
                        case 'library': $redirect_url = "department/library/dashboard.php"; break;
                        case 'faculty': $redirect_url = "department/faculty/dashboard.php"; break;
                        case 'property': $redirect_url = "department/property/dashboard.php"; break;
                        case 'dean': $redirect_url = "department/dean/dashboard.php"; break;
                        case 'student': $redirect_url = "student/dashboard.php"; break;
                        default: $redirect_url = "student/dashboard.php"; break;
                    }
                    
                    $_SESSION['redirect_url'] = $redirect_url;
                    header("Location: login.php");
                    exit;
                }
            } else {
                if ($user) {
                    $new_attempts = ($user['failed_attempts'] ?? 0) + 1;
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE email = ?");
                    $stmt->execute([$new_attempts, $email]);

                    $remaining_attempts = $max_attempts - $new_attempts;
                    if ($remaining_attempts > 0) {
                        $error = "Invalid email or password. {$remaining_attempts} attempt(s) remaining.";
                    } else {
                        $error = "Too many failed attempts. Account locked for 15 minutes.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            }
        }
    }
}

$show_welcome = isset($_SESSION['login_success']) && $_SESSION['login_success'];
$user_name = $_SESSION['user_name'] ?? 'User';
$redirect_url = $_SESSION['redirect_url'] ?? 'student/dashboard.php';

// Check for Remember Me Cookie
$saved_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';

if ($show_welcome) {
    unset($_SESSION['login_success']);
}

$compliments = [
    "Your smile brightens our system! ✨",
    "Looking great today! Ready to conquer? 💪",
    "The dashboard missed you! 🌟",
    "Your presence makes everything better! 🎯",
    "Ready to make today amazing? 🚀",
    "Your energy is contagious! Keep shining! ⭐",
    "The system is happier with you here! 😊",
    "You're making great progress! 📈",
    "Your dedication is inspiring! 🎓",
    "Today is going to be a great day! 🌈"
];
$random_compliment = $compliments[array_rand($compliments)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EAS-CE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        }
        
        .login-box {
            position: relative;
            z-index: 10;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
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
        
        .login-box h3 {
            text-align: center;
            font-weight: bold;
            margin-bottom: 25px;
            font-size: clamp(1.5rem, 5vw, 2rem);
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
        
        .btn-login {
            width: 100%;
            border-radius: 30px;
            background-color: #007bff;
            border: none;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: clamp(0.875rem, 2vw, 1rem);
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        /* Password Eye Icon Style */
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #ddd;
            z-index: 2;
        }

        .toggle-password:hover {
            color: #fff;
        }

        /* Remember Me Styles */
        .form-check-input {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .top-brand {
            position: fixed;
            top: 20px;
            left: 20px;
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
        
        .top-brand:hover img {
            transform: scale(1.05);
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
        
        .error-msg {
            background: rgba(255, 0, 0, 0.3);
            color: #ffb3b3;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            animation: shake 0.5s ease;
            font-size: clamp(0.75rem, 2vw, 0.875rem);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
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
        
        .links-container {
            margin-top: 20px;
            text-align: center;
        }
        
        .links-container small {
            display: block;
            margin: 8px 0;
            font-size: clamp(0.75rem, 2vw, 0.875rem);
        }

        /* Welcome Screen Styles */
        .welcome-screen {
            position: fixed;
            inset: 0;
            background: 'assets/landing-bg.png';
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.5s ease;
            backdrop-filter: blur(5px);
            background: rgba(0, 0, 0, 0.5);
        }
        
        .welcome-content {
            text-align: center;
            color: white;
            animation: scaleIn 0.6s ease;
            padding: 20px;
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .success-checkmark {
            font-size: 100px;
            color: #4caf50;
            animation: checkmarkPop 0.8s ease;
            text-shadow: 0 4px 15px rgba(76, 175, 80, 0.5);
            margin-bottom: 20px;
        }
        
        @keyframes checkmarkPop {
            0% {
                opacity: 0;
                transform: scale(0) rotate(-180deg);
            }
            50% {
                transform: scale(1.3) rotate(10deg);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }
        
        .welcome-text h1 {
            font-size: clamp(2rem, 6vw, 3.5rem);
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
            animation: slideInFromLeft 0.6s ease 0.3s both;
        }
        
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .welcome-text h2 {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 600;
            margin-bottom: 20px;
            opacity: 0.95;
            animation: slideInFromRight 0.6s ease 0.5s both;
        }
        
        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .compliment-text {
            font-size: clamp(1rem, 2.5vw, 1.3rem);
            opacity: 0.9;
            margin-bottom: 30px;
            animation: fadeInUp 0.6s ease 0.7s both;
            font-style: italic;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading-dots {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            animation: fadeInUp 0.6s ease 0.9s both;
        }
        
        .loading-dot {
            width: 12px;
            height: 12px;
            background: white;
            border-radius: 50%;
            animation: dotPulse 1.5s ease infinite;
        }
        
        .loading-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .loading-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes dotPulse {
            0%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.2);
                opacity: 1;
            }
        }
        
        .redirect-info {
            margin-top: 20px;
            font-size: clamp(0.85rem, 2vw, 1rem);
            opacity: 0.8;
            animation: fadeInUp 0.6s ease 1.1s both;
        }

        /* Progress Bar */
        .progress-container {
            width: 80%;
            max-width: 400px;
            height: 4px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            margin: 30px auto 0;
            overflow: hidden;
            animation: fadeInUp 0.6s ease 1.3s both;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
            width: 0%;
            animation: progressBar 3.5s ease forwards;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }
        
        @keyframes progressBar {
            to { width: 100%; }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .login-box {
                padding: 30px 20px;
            }
            .top-brand {
                top: 15px;
                left: 15px;
            }
            .brand-text small {
                display: none;
            }
            .welcome-content {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 25px 15px;
            }
            .top-brand {
                top: 10px;
                left: 10px;
            }
            .top-brand img {
                width: 70px;
                height: 70px;
            }
            .brand-text h5 {
                font-size: 1rem;
            }
            .success-checkmark {
                font-size: 70px;
            }
        }
    </style>
    <?php if ($show_welcome): ?>
    <script>
        setTimeout(function() {
            window.location.href = '<?= htmlspecialchars($redirect_url) ?>';
        }, 2500);
    </script>
    <?php endif; ?>
</head>
<body>
    <?php if ($show_welcome): ?>
    <div class="welcome-screen">
        <div class="welcome-content">
            <div class="success-checkmark">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="welcome-text">
                <h1>Welcome Back!</h1>
                <h2><?= htmlspecialchars($user_name) ?></h2>
                <p class="compliment-text">
                    <?= htmlspecialchars($random_compliment) ?>
                </p>
            </div>
            <div class="loading-dots">
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
            </div>
            <p class="redirect-info">
                <i class="fas fa-rocket"></i> Taking you to your dashboard...
            </p>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="overlay"></div>

    <a href="index.php" class="top-brand">
        <img src="assets/logo.png" alt="DYCI Logo">
        <div class="brand-text">
            <h5>EAS-CE</h5>
            <small>Easy Access Student Clearance & E-Documents</small>
        </div>
    </a>

    <div class="login-box">
        <h3>Log In</h3>

        <?php if ($error): ?>
            <div class="error-msg mb-3">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" 
                       value="<?php echo htmlspecialchars($saved_email); ?>" required>
            </div>
            
            <div class="mb-3 password-wrapper">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>



            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="links-container">
            <small>Don't have an account? <a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a></small>
            <small><a href="forgot-password.php" class="forgot-link"><i class="fas fa-key"></i> Forgot Password?</a></small>
        </div>
    </div>
    
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle the eye slash icon
            this.classList.toggle('fa-eye-slash');
        });
    </script>
    <?php endif; ?>
</body>
</html>