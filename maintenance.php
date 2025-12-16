<?php
require_once 'config.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is admin, redirect to their dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header("Location: " . SITE_URL . "/admin/dashboard.php");
    exit;
}

// Get system settings
$settings = getSystemSettings($conn);
$site_name = $settings['site_name'] ?? 'EAS-CE';
$site_email = $settings['site_email'] ?? 'admin@dyci.edu.ph';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance | <?php echo htmlspecialchars($site_name); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
     <!-- Your reusable responsive CSS -->
    <style>
        /* Add these responsive styles to maintenance.php */

body {
    padding: 20px;
}

.maintenance-box {
    width: 100%;
    max-width: 500px;
    padding: 40px 35px;
}

.maintenance-box h1 {
    font-size: clamp(1.75rem, 5vw, 2.5rem);
    margin-bottom: 15px;
}

.maintenance-box p {
    font-size: clamp(0.95rem, 2.5vw, 1.1rem);
    margin-bottom: 25px;
}

.icon-container {
    font-size: clamp(45px, 10vw, 60px);
    margin-bottom: 20px;
}

.contact-admin {
    font-size: clamp(0.8rem, 2vw, 0.9rem);
    padding: 15px;
}

.contact-admin strong {
    font-size: clamp(0.85rem, 2vw, 0.95rem);
}

.back-btn {
    margin-top: 20px;
    padding: 12px 20px;
    font-size: clamp(0.875rem, 2vw, 1rem);
}

/* Mobile styles */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .maintenance-box {
        padding: 30px 25px;
        max-width: 95%;
    }
    
    .maintenance-box h1 {
        margin-bottom: 12px;
    }
    
    .maintenance-box p {
        margin-bottom: 20px;
    }
    
    .icon-container {
        margin-bottom: 15px;
    }
    
    .contact-admin {
        padding: 12px;
        margin-top: 15px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }
    
    .maintenance-box {
        padding: 25px 20px;
        border-radius: 15px;
    }
    
    .maintenance-box h1 {
        margin-bottom: 10px;
    }
    
    .maintenance-box p {
        margin-bottom: 15px;
    }
    
    .contact-admin {
        padding: 10px;
        font-size: 0.8rem;
        margin-top: 12px;
    }
    
    .back-btn {
        padding: 10px 18px;
        margin-top: 15px;
    }
}

/* Landscape orientation on mobile */
@media (max-height: 600px) and (orientation: landscape) {
    body {
        padding: 10px;
        align-items: flex-start;
    }
    
    .maintenance-box {
        padding: 20px;
        margin: 15px auto;
    }
    
    .icon-container {
        font-size: 40px;
        margin-bottom: 10px;
    }
    
    .maintenance-box h1 {
        margin-bottom: 8px;
    }
    
    .maintenance-box p {
        margin-bottom: 12px;
    }
    
    .contact-admin {
        padding: 10px;
        margin-top: 10px;
    }
    
    .back-btn {
        margin-top: 12px;
    }
}
        body {
            background: url('assets/landing-bg.png') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .maintenance-box {
            position: relative;
            z-index: 10;
            width: 500px;
            padding: 40px 35px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            text-align: center;
            color: white;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);

            /* animation */
            animation: fadeUp 0.7s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .maintenance-box h1 {
            font-weight: bold;
            margin-bottom: 15px;
        }

        .maintenance-box p {
            font-size: 1.1rem;
            margin-bottom: 25px;
            opacity: 0.9;
        }

        .icon-container {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ffc107;
            animation: glow 2s infinite ease-in-out;
        }

        @keyframes glow {
            0% { text-shadow: 0 0 5px #ffc107; }
            50% { text-shadow: 0 0 20px #ffc107; }
            100% { text-shadow: 0 0 5px #ffc107; }
        }

        .contact-admin {
            font-size: 0.9rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .contact-admin strong {
            color: #ffc107;
        }

        .back-btn {
            margin-top: 20px;
            background: #007bff;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            color: white;
            font-weight: bold;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        }
    </style>
</head>
<body>

<div class="overlay"></div>

<div class="maintenance-box">
    <div class="icon-container">
        <i class="fas fa-tools"></i>
    </div>

    <h1>System Under Maintenance</h1>
    <p>
        We're currently performing scheduled maintenance to improve our services.<br>
        The system will be back online shortly.
    </p>

    <p style="font-size: 1rem; opacity: 0.85;">
        <strong>We apologize for any inconvenience.</strong>
    </p>

    <div class="contact-admin">
        <i class="fas fa-envelope"></i> For urgent matters, please contact us at:<br>
        <strong><?php echo htmlspecialchars($site_email); ?></strong>
    </div>

    <a href="<?php echo SITE_URL; ?>/login.php" class="btn back-btn mt-3">
        <i class="fas fa-refresh"></i> Refresh the Page
    </a>
</div>

</body>
</html>