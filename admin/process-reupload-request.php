<?php
require_once '../config.php';
checkAuth(['admin']);

header('Content-Type: application/json');

try {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'approved' or 'rejected'
    $admin_response = $_POST['admin_response'] ?? '';
    
    if (!$request_id || !$action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    if ($action === 'rejected' && empty($admin_response)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a reason for rejection']);
        exit;
    }
    
    // Get reupload request details
    $stmt = $conn->prepare("
        SELECT prr.*, dr.id as doc_request_id, dr.student_id 
        FROM payment_reupload_requests prr
        JOIN document_requests dr ON prr.request_id = dr.id
        WHERE prr.id = ?
    ");
    $stmt->execute([$request_id]);
    $reuploadReq = $stmt->fetch();
    
    if (!$reuploadReq) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Update reupload request status
        $stmt = $conn->prepare("
            UPDATE payment_reupload_requests 
            SET status = ?, 
                admin_response = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$action, $admin_response, $request_id]);
        
        if ($action === 'approved') {
            // Update document request to allow re-upload
            $stmt = $conn->prepare("
                UPDATE document_requests 
                SET payment_status = 'reupload_approved',
                    status = 'for_payment',
                    notes = CONCAT(COALESCE(notes, ''), '\n[Re-upload Approved by Admin on ', ?, ']')
                WHERE id = ?
            ");
            $stmt->execute([date('Y-m-d H:i:s'), $reuploadReq['doc_request_id']]);
            
            // Delete old payment record so student can upload new one
            $stmt = $conn->prepare("DELETE FROM payments WHERE request_id = ?");
            $stmt->execute([$reuploadReq['doc_request_id']]);
            
            // Create notification for student
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, created_at)
                VALUES (?, 'payment_reupload_approved', ?, NOW())
            ");
            $notifMessage = "Your payment re-upload request has been approved. You can now upload new payment details for Request #" . $reuploadReq['doc_request_id'];
            $stmt->execute([$reuploadReq['student_id'], $notifMessage]);
            
        } else if ($action === 'rejected') {
            // Update document request
            $stmt = $conn->prepare("
                UPDATE document_requests 
                SET payment_status = 'reupload_rejected',
                    notes = CONCAT(COALESCE(notes, ''), '\n[Re-upload Rejected by Admin on ', ?, ': ', ?, ']')
                WHERE id = ?
            ");
            $stmt->execute([date('Y-m-d H:i:s'), $admin_response, $reuploadReq['doc_request_id']]);
            
            // Create notification for student
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, created_at)
                VALUES (?, 'payment_reupload_rejected', ?, NOW())
            ");
            $notifMessage = "Your payment re-upload request has been rejected. Reason: " . $admin_response;
            $stmt->execute([$reuploadReq['student_id'], $notifMessage]);
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Process reupload request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error occurred']);
}
?>