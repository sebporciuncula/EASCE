<?php
// email_notifications.php - Centralized email notification system

function sendStatusUpdateEmail($conn, $request_id, $student_id, $new_status, $action_by = null, $notes = '') {
    // Get student details
    $stmt = $conn->prepare("
        SELECT u.email, sp.fullname, sp.email_notifications 
        FROM users u 
        JOIN student_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    // Check if student wants email notifications
    if (!$student || !$student['email_notifications']) {
        return false;
    }
    
    // Get request details
    $stmt = $conn->prepare("
        SELECT request_type, quantity, purpose, payment_amount 
        FROM document_requests 
        WHERE id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    // Get action taker's name if provided
    $action_taker = '';
    if ($action_by) {
        $stmt = $conn->prepare("
            SELECT sp.fullname as staff_name 
            FROM staff_profiles sp 
            WHERE sp.user_id = ?
            UNION
            SELECT sp.fullname as staff_name 
            FROM student_profiles sp 
            WHERE sp.user_id = ?
        ");
        $stmt->execute([$action_by, $action_by]);
        $actor = $stmt->fetch();
        $action_taker = $actor ? $actor['staff_name'] : 'System';
    }
    
    // Build email content based on status
    $subject = '';
    $message = '';
    
    switch($new_status) {
        case 'pending':
            $subject = 'Document Request Received - Request #' . $request_id;
            $message = buildEmailTemplate(
                'Request Received',
                "Dear {$student['fullname']},",
                "Your request for <strong>{$request['request_type']}</strong> has been received and is now pending review by the Registrar's Office.",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Quantity' => $request['quantity'],
                    'Status' => 'Pending Review'
                ],
                'You will receive another notification once your request has been reviewed.'
            );
            break;
            
        case 'confirmed':
            $subject = 'Request Approved - Action Required - Request #' . $request_id;
            $payment_formatted = number_format($request['payment_amount'], 2);
            $message = buildEmailTemplate(
                'Request Approved',
                "Dear {$student['fullname']},",
                "Great news! Your request has been approved by <strong>{$action_taker}</strong> from the Registrar's Office.",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Payment Amount' => '₱' . $payment_formatted,
                    'Approved By' => $action_taker,
                    'Status' => 'Ready for Payment'
                ],
                '<strong>Next Step:</strong> Please proceed to pay the required amount of <strong>₱' . $payment_formatted . '</strong> through the EAS-CE payment portal or at the Cashier\'s Office.'
            );
            break;
            
        case 'rejected':
            $subject = 'Request Not Approved - Request #' . $request_id;
            $message = buildEmailTemplate(
                'Request Not Approved',
                "Dear {$student['fullname']},",
                "Unfortunately, your request could not be approved at this time.",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Rejected By' => $action_taker,
                    'Reason' => $notes ?: 'Not specified',
                    'Status' => 'Rejected'
                ],
                'If you have questions about this decision, please contact the Registrar\'s Office during office hours.'
            );
            break;
            
        case 'paid':
            $payment_date = date('F j, Y g:i A');
            $subject = 'Payment Received - Request #' . $request_id;
            $message = buildEmailTemplate(
                'Payment Received',
                "Dear {$student['fullname']},",
                "Your payment has been submitted and is awaiting verification by the Cashier's Office.",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Payment Date' => $payment_date,
                    'Amount Paid' => '₱' . number_format($request['payment_amount'], 2),
                    'Status' => 'Pending Payment Verification'
                ],
                'You will receive a notification once your payment has been verified.'
            );
            break;
            
        case 'payment_verified':
            $subject = 'Payment Verified - Processing Started - Request #' . $request_id;
            $message = buildEmailTemplate(
                'Payment Verified',
                "Dear {$student['fullname']},",
                "Your payment has been verified by <strong>{$action_taker}</strong> from the Cashier's Office. Document processing has begun.",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Verified By' => $action_taker,
                    'Status' => 'Processing'
                ],
                'Your document is now being prepared. You will be notified when it is ready for release.'
            );
            break;
            
        case 'payment_rejected':
            $subject = 'Payment Verification Failed - Request #' . $request_id;
            $message = buildEmailTemplate(
                'Payment Verification Failed',
                "Dear {$student['fullname']},",
                "Your payment could not be verified.",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Reason' => $notes ?: 'Payment proof unclear or invalid',
                    'Reviewed By' => $action_taker,
                    'Status' => 'Payment Rejected'
                ],
                '<strong>Action Required:</strong> Please upload a clearer payment proof or contact the Cashier\'s Office for assistance.'
            );
            break;
            
        case 'ready':
            $subject = 'Document Ready for Release - Request #' . $request_id;
            $message = buildEmailTemplate(
                'Document Ready',
                "Dear {$student['fullname']},",
                "Your document is now ready for release!",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Prepared By' => $action_taker,
                    'Status' => 'Ready for Release'
                ],
                'Please proceed to the Frontline Office during office hours to claim your document. Bring a valid ID for verification.'
            );
            break;
            
        case 'released':
            $release_date = date('F j, Y g:i A');
            $subject = 'Document Released - Request #' . $request_id;
            $message = buildEmailTemplate(
                'Document Released',
                "Dear {$student['fullname']},",
                "Your document has been successfully released.",
                [
                    'Request ID' => $request_id,
                    'Document Type' => $request['request_type'],
                    'Released By' => $action_taker,
                    'Release Date' => $release_date,
                    'Status' => 'Completed'
                ],
                'Thank you for using EAS-CE. If you need assistance with this document, please contact the Registrar\'s Office.'
            );
            break;
    }
    
    // Send email
    if ($subject && $message) {
        return sendEmail($student['email'], $subject, $message);
    }
    
    return false;
}

function buildEmailTemplate($title, $greeting, $intro, $details, $footer) {
    $details_html = '';
    foreach ($details as $key => $value) {
        $details_html .= "
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #555;'>{$key}:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e0e0e0; color: #333;'>{$value}</td>
            </tr>
        ";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                                <h1 style='margin: 0; color: #ffffff; font-size: 24px;'>{$title}</h1>
                                <p style='margin: 10px 0 0 0; color: #ffffff; opacity: 0.9;'>EAS-CE Document Management System</p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style='padding: 30px;'>
                                <p style='margin: 0 0 15px 0; color: #333; font-size: 16px;'>{$greeting}</p>
                                <p style='margin: 0 0 20px 0; color: #666; line-height: 1.6;'>{$intro}</p>
                                
                                <!-- Details Table -->
                                <table width='100%' cellpadding='0' cellspacing='0' style='margin: 20px 0; border: 1px solid #e0e0e0; border-radius: 4px;'>
                                    {$details_html}
                                </table>
                                
                                <!-- Footer Message -->
                                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 20px;'>
                                    <p style='margin: 0; color: #555; font-size: 14px; line-height: 1.5;'>{$footer}</p>
                                </div>
                                
                                <!-- Action Button -->
                                <div style='text-align: center; margin-top: 30px;'>
                                    <a href='" . SITE_URL . "/student/dashboard.php' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 25px; font-weight: 600;'>View My Requests</a>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                                <p style='margin: 0 0 10px 0; color: #999; font-size: 12px;'>
                                    Dr. Yanga's Colleges, Inc.<br>
                                    EAS-CE System - Easy Access Student Clearance & E-Documents
                                </p>
                                <p style='margin: 0; color: #999; font-size: 11px;'>
                                    This is an automated message. Please do not reply to this email.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}

// Function to create notification in database
function createNotification($conn, $user_id, $type, $message) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, message, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $type, $message]);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

// Combined function for both email and in-app notification
function notifyStudent($conn, $request_id, $student_id, $status, $action_by = null, $notes = '') {
    // Send email notification
    $email_sent = sendStatusUpdateEmail($conn, $request_id, $student_id, $status, $action_by, $notes);
    
    // Create in-app notification
    $notification_messages = [
        'pending' => "Your document request #$request_id has been received and is pending review.",
        'confirmed' => "Your request #$request_id has been approved. Please proceed with payment.",
        'rejected' => "Your request #$request_id was not approved. Reason: $notes",
        'paid' => "Payment for request #$request_id received. Awaiting verification.",
        'payment_verified' => "Payment verified! Your request #$request_id is now being processed.",
        'payment_rejected' => "Payment for request #$request_id could not be verified. $notes",
        'ready' => "Your document for request #$request_id is ready for release!",
        'released' => "Request #$request_id has been completed and released."
    ];
    
    $message = $notification_messages[$status] ?? "Status updated for request #$request_id";
    $notification_created = createNotification($conn, $student_id, $status, $message);
    
    return [
        'email_sent' => $email_sent,
        'notification_created' => $notification_created
    ];
}
?>