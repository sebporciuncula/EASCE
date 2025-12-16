<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAS-CE | Easy Access Student Clearance & E-Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background: url('assets/landing-bg.png') center/cover no-repeat fixed;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(132, 161, 214, 0.95);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar.scrolled .navbar-brand,
        .navbar.scrolled .nav-link {
            color: #333 !important;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            color: white !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-brand img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .nav-link {
            color: white !important;
            margin: 0 15px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
        }

        .btn-login {
            background: white;
            color: #667eea;
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: 2px solid white;
        }

        .btn-login:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding-top: 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            animation: float 6s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-content {
            text-align: center;
            z-index: 1;
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .btn-get-started {
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-get-started:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        /* How It Works Section */
        .how-it-works {
            background: white;
            padding: 100px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .section-title p {
            color: #666;
            font-size: 1.1rem;
        }

        .step-card {
            background: linear-gradient(135deg, #667eea 0%, #010d25  100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .step-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .step-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .step-card:hover::before {
            top: -100%;
            right: -100%;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: white;
            color: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
            position: relative;
            z-index: 1;
        }

        .step-card i {
            font-size: 3rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .step-card h4 {
            font-weight: bold;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .step-card p {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Features Section */
        .features {
            background: linear-gradient(135deg, #667eea 0%, #010d25  100%);
            color: white;
            padding: 100px 0;
        }

        .feature-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-box:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
        }

        .feature-box i {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .feature-box h5 {
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Footer */
        footer {
            background: #2d3748;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        footer p {
            margin: 0;
            opacity: 0.8;
        }

        footer a {
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        footer a:hover {
            color: #764ba2;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/logo.png" alt="DYCI Logo">
                EAS-CE
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a href="login.php" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1><i class="fas fa-graduation-cap"></i> Welcome to EAS-CE</h1>
                <p>Easy Access Student Clearance & E-Documents System</p>
                <p class="mb-4">Streamline your document requests and clearance process at Dr. Yanga's Colleges, Inc.</p>
                <a href="#how-it-works" class="btn btn-get-started">
                    <i class="fas fa-play-circle"></i> Get Started
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2><i class="fas fa-list-check"></i> How to Request Documents</h2>
                <p>Follow these simple steps to request your documents online</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <i class="fas fa-user-plus"></i>
                        <h4>Create Account</h4>
                        <p>Register using your desire email and complete your profile with accurate information.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <i class="fas fa-file-alt"></i>
                        <h4>Submit Request</h4>
                        <p>Choose your document type, fill out the request form, and upload required files.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <i class="fas fa-clock"></i>
                        <h4>Track Progress</h4>
                        <p>Monitor your request status in real-time through your dashboard with email notifications.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <i class="fas fa-check-circle"></i>
                        <h4>Receive Document</h4>
                        <p>Get notified when ready, pay online, and collect your verified document.</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <h4 class="mb-4">Available Documents</h4>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You can request: 
                            <strong>Transcripts of Records</strong>, 
                            <strong>Certificates of Enrollment</strong>, 
                            <strong>Certificates of Grades</strong>, 
                            <strong>Diplomas</strong>, 
                            <strong>Good Moral Certificates</strong>, and more!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2 style="color: white;"><i class="fas fa-star"></i> System Features</h2>
                <p style="color: rgba(255,255,255,0.9);">Why choose EAS-CE for your document requests?</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <i class="fas fa-clock"></i>
                        <h5>24/7 Access</h5>
                        <p>Submit and track requests anytime, anywhere with our always-available platform.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <i class="fas fa-bolt"></i>
                        <h5>Fast Processing</h5>
                        <p>Automated workflows reduce processing time by up to 75% compared to manual methods.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <i class="fas fa-shield-alt"></i>
                        <h5>Secure & Verified</h5>
                        <p>QR codes and digital signatures ensure document authenticity and data protection.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <i class="fas fa-bell"></i>
                        <h5>Real-Time Updates</h5>
                        <p>Receive instant email notifications for every status change in your request.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <i class="fas fa-credit-card"></i>
                        <h5>Flexible Payment</h5>
                        <p>Pay online - choose the payment method that works for you.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <i class="fas fa-mobile-alt"></i>
                        <h5>Mobile Friendly</h5>
                        <p>Fully responsive design works seamlessly on desktop, tablet, and mobile devices.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 EAS-CE | Dr. Yanga's Colleges, Inc. | <a href="data-privacy.php">Privacy Policy</a></p>
            <p class="mt-2">
                <small>Developed by: Rogelio, Porciuncula</small>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll for anchor links - ✅ FIXED VERSION
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                
                // ✅ Check if href is just "#" or empty
                if (!href || href === '#') {
                    e.preventDefault();
                    return;
                }
                
                const target = document.querySelector(href);
                
                // ✅ Only scroll if target exists
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>