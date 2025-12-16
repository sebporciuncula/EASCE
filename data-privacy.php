<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Privacy Policy | EAS-CE</title>
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
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            color: #333 !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-brand img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .navbar-brand .brand-text {
            display: flex;
            flex-direction: column;
        }

        .navbar-brand .brand-text div {
            font-weight: bold;
            font-size: 1.5rem;
            line-height: 1.2;
        }

        .nav-link {
            color: #333 !important;
            margin: 0 15px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #667eea !important;
            transform: translateY(-2px);
        }

        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Content Section */
        .privacy-content {
            margin-top: 100px;
            padding: 60px 0;
            min-height: calc(100vh - 180px);
        }

        .content-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.8s ease;
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

        .content-box h1 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }

        .content-box .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .content-box h2 {
            color: #333;
            font-weight: bold;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .content-box h3 {
            color: #667eea;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }

        .content-box p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
            text-align: justify;
        }

        .content-box ul {
            margin: 20px 0;
            padding-left: 30px;
        }

        .content-box li {
            color: #555;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .highlight-box {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            border-radius: 10px;
        }

        .highlight-box strong {
            color: #667eea;
        }

        .contact-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
        }

        .contact-box h4 {
            color: #667eea;
            margin-bottom: 15px;
        }

        .contact-box p {
            margin-bottom: 10px;
        }

        .contact-box i {
            color: #667eea;
            margin-right: 10px;
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
            .content-box {
                padding: 30px 20px;
            }

            .content-box h1 {
                font-size: 2rem;
            }

            .content-box h2 {
                font-size: 1.5rem;
            }

            .content-box h3 {
                font-size: 1.2rem;
            }

            .navbar-brand .brand-text small {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/logo.png" alt="DYCI Logo">
                <div class="brand-text">
                    <div>EAS-CE</div>
                    <small style="font-size: 0.65rem; opacity: 0.8; font-weight: 400; display: block; line-height: 1.2;">Easy Access Student Clearance & E-Documents</small>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item ms-3">
                        <a href="index.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Privacy Content -->
    <section class="privacy-content">
        <div class="container">
            <div class="content-box">
                <h1><i class="fas fa-shield-alt"></i> Data Privacy Policy</h1>
                <p class="subtitle">Dr. Yanga's Colleges, Inc. - EAS-CE System</p>

                <div class="highlight-box">
                    <p><strong>Effective Date:</strong> November 17, 2025</p>
                    <p><strong>Last Updated:</strong> November 17, 2025</p>
                </div>

                <h2>1. Introduction</h2>
                <p>Dr. Yanga's Colleges, Inc. (DYCI) is committed to protecting the privacy and security of personal information collected through the Easy Access Student Clearance & E-Documents (EAS-CE) system. This Data Privacy Policy outlines how we collect, use, store, and protect your personal data in compliance with the Data Privacy Act of 2012 (Republic Act No. 10173) and its Implementing Rules and Regulations.</p>

                <h2>2. Scope and Application</h2>
                <p>This policy applies to:</p>
                <ul>
                    <li>Current and former students of Dr. Yanga's Colleges, Inc.</li>
                    <li>Faculty and staff members using the EAS-CE system</li>
                    <li>All users accessing the EAS-CE platform</li>
                    <li>Personal data collected through the system for clearance and document request purposes</li>
                </ul>

                <h2>3. Personal Information We Collect</h2>
                <h3>3.1 Student Information</h3>
                <ul>
                    <li>Full name, student number, and contact details (email address, phone number)</li>
                    <li>Academic information (program, year level, student status)</li>
                    <li>Document request history and transaction records</li>
                    <li>Payment information and financial records</li>
                    <li>Profile photographs and identification documents</li>
                </ul>

                <h3>3.2 System Usage Data</h3>
                <ul>
                    <li>Login credentials and account information</li>
                    <li>IP addresses and device information</li>
                    <li>System access logs and timestamps</li>
                    <li>Document tracking and status updates</li>
                </ul>

                <h2>4. Purpose of Data Collection</h2>
                <p>We collect and process your personal information for the following purposes:</p>
                <ul>
                    <li><strong>Identity Verification:</strong> To verify your identity and ensure only authorized users access the system</li>
                    <li><strong>Document Processing:</strong> To process clearance requests and issue academic documents</li>
                    <li><strong>Communication:</strong> To send notifications, updates, and important announcements regarding your requests</li>
                    <li><strong>Record Keeping:</strong> To maintain accurate academic and transaction records as required by law</li>
                    <li><strong>System Improvement:</strong> To analyze usage patterns and improve system functionality</li>
                    <li><strong>Security:</strong> To protect against unauthorized access and fraudulent activities</li>
                </ul>

                <h2>5. Legal Basis for Processing</h2>
                <p>We process your personal data based on the following legal grounds:</p>
                <ul>
                    <li><strong>Consent:</strong> You have given explicit consent for specific processing activities</li>
                    <li><strong>Contract:</strong> Processing is necessary for the performance of your enrollment contract with DYCI</li>
                    <li><strong>Legal Obligation:</strong> Compliance with educational regulations and retention requirements</li>
                    <li><strong>Legitimate Interests:</strong> For purposes that are in the legitimate interests of DYCI and do not override your fundamental rights</li>
                </ul>

                <h2>6. Data Sharing and Disclosure</h2>
                <p>DYCI does not sell, rent, or trade your personal information. We may share your data only in the following circumstances:</p>
                <ul>
                    <li><strong>Internal Departments:</strong> With authorized DYCI offices (Registrar, Cashier, Academic departments) for processing your requests</li>
                    <li><strong>Third-Party Service Providers:</strong> With trusted vendors who assist in system operations (subject to confidentiality agreements)</li>
                    <li><strong>Legal Requirements:</strong> When required by law, court order, or government regulations</li>
                    <li><strong>Emergency Situations:</strong> To protect the vital interests of individuals in emergency situations</li>
                </ul>

                <h2>7. Data Security Measures</h2>
                <p>We implement comprehensive security measures to protect your personal information:</p>
                <ul>
                    <li><strong>Encryption:</strong> All data transmissions are encrypted using industry-standard protocols</li>
                    <li><strong>Access Controls:</strong> Role-based access restrictions and multi-factor authentication</li>
                    <li><strong>Regular Audits:</strong> Periodic security assessments and vulnerability testing</li>
                    <li><strong>Secure Storage:</strong> Data stored on secure servers with backup and disaster recovery procedures</li>
                    <li><strong>Staff Training:</strong> Regular training for personnel handling personal data</li>
                    <li><strong>Rate Limiting:</strong> Account lockout mechanisms to prevent unauthorized access attempts</li>
                </ul>

                <h2>8. Data Retention</h2>
                <p>Personal information is retained for the following periods:</p>
                <ul>
                    <li><strong>Active Student Records:</strong> During enrollment and up to 5 years after graduation or last attendance</li>
                    <li><strong>Transaction Records:</strong> Minimum of 5 years for audit and legal compliance purposes</li>
                    <li><strong>System Logs:</strong> 1-2 years for security monitoring and troubleshooting</li>
                    <li><strong>Archived Records:</strong> As required by CHED regulations and institutional policies</li>
                </ul>

                <h2>9. Your Rights as a Data Subject</h2>
                <p>Under the Data Privacy Act of 2012, you have the following rights:</p>
                <ul>
                    <li><strong>Right to Information:</strong> To be informed about the collection and processing of your personal data</li>
                    <li><strong>Right to Access:</strong> To obtain a copy of your personal data in our records</li>
                    <li><strong>Right to Rectification:</strong> To correct inaccurate or incomplete personal information</li>
                    <li><strong>Right to Erasure:</strong> To request deletion of your data (subject to legal retention requirements)</li>
                    <li><strong>Right to Object:</strong> To object to certain types of processing</li>
                    <li><strong>Right to Data Portability:</strong> To receive your data in a structured, commonly used format</li>
                    <li><strong>Right to File a Complaint:</strong> To lodge a complaint with the National Privacy Commission</li>
                </ul>

                <h2>10. Cookies and Tracking Technologies</h2>
                <p>The EAS-CE system uses session cookies and tracking technologies to:</p>
                <ul>
                    <li>Maintain your login session and preferences</li>
                    <li>Enhance system performance and user experience</li>
                    <li>Analyze system usage and identify technical issues</li>
                </ul>
                <p>You can manage cookie preferences through your browser settings, though disabling cookies may affect system functionality.</p>

                <h2>11. Updates to This Policy</h2>
                <p>DYCI reserves the right to update this Data Privacy Policy periodically to reflect changes in:</p>
                <ul>
                    <li>Legal and regulatory requirements</li>
                    <li>System features and functionality</li>
                    <li>Data processing practices</li>
                </ul>
                <p>Users will be notified of significant changes through email or system announcements. Continued use of the EAS-CE system constitutes acceptance of the updated policy.</p>

                <h2>12. Contact Information</h2>
                <div class="contact-box">
                    <h4><i class="fas fa-address-card"></i> Data Protection Officer</h4>
                    <p><i class="fas fa-building"></i> <strong>Institution:</strong> Dr. Yanga's Colleges, Inc.</p>
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> Barangay San Vicente, Apalit, Pampanga, Philippines 2016</p>
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong> dpo@dyci.edu.ph</p>
                    <p><i class="fas fa-phone"></i> <strong>Phone:</strong> (045) 861-1177</p>
                    <p><i class="fas fa-globe"></i> <strong>Website:</strong> <a href="https://dyci.edu.ph" target="_blank">www.dyci.edu.ph</a></p>
                </div>

                <div class="highlight-box">
                    <p><strong><i class="fas fa-exclamation-triangle"></i> Important Notice:</strong></p>
                    <p>For questions, concerns, or requests regarding your personal data, please contact our Data Protection Officer using the information provided above. We are committed to addressing your inquiries within 30 days of receipt.</p>
                </div>

                <h2>13. Complaints and Enforcement</h2>
                <p>If you believe your data privacy rights have been violated, you may:</p>
                <ul>
                    <li>Contact DYCI's Data Protection Officer for internal resolution</li>
                    <li>File a complaint with the National Privacy Commission (NPC)</li>
                    <li>Seek legal remedies as provided under the Data Privacy Act of 2012</li>
                </ul>

                <div class="contact-box">
                    <h4><i class="fas fa-landmark"></i> National Privacy Commission</h4>
                    <p><i class="fas fa-map-marker-alt"></i> 5th Floor, Philippine International Convention Center (PICC), Vicente Sotto St., Pasay City, Metro Manila</p>
                    <p><i class="fas fa-envelope"></i> info@privacy.gov.ph</p>
                    <p><i class="fas fa-phone"></i> (02) 8234-2228</p>
                    <p><i class="fas fa-globe"></i> <a href="https://www.privacy.gov.ph" target="_blank">www.privacy.gov.ph</a></p>
                </div>

                <div class="text-center mt-5">
                    <p><strong>Acknowledgment:</strong></p>
                    <p>By using the EAS-CE system, you acknowledge that you have read, understood, and agree to this Data Privacy Policy.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 EAS-CE | Dr. Yanga's Colleges, Inc. | <a href="data-privacy.php">Privacy Policy</a> </p>
            <p class="mt-2">
                <small>Developed by: Rogelio, Porciuncula</small>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
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