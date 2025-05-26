<?php
require_once 'db_connection.php';

$response = json_decode(file_get_contents('php://input'), true);

if (isset($response['Body']['stkCallback'])) {
    $callback = $response['Body']['stkCallback'];
    $checkoutRequestID = $callback['CheckoutRequestID'];
    $resultCode = $callback['ResultCode'];
    
    try {
        // Get payment record
        $stmt = $conn->prepare("SELECT * FROM payments WHERE mpesa_checkout_id = ?");
        $stmt->execute([$checkoutRequestID]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            // Update payment status
            $status = ($resultCode == 0) ? 'completed' : 'failed';
            $conn->prepare("UPDATE payments SET status = ? WHERE id = ?")
                ->execute([$status, $payment['id']]);
            
            // Update job payment status
            $conn->prepare("UPDATE jobs SET payment_status = ? WHERE id = ?")
                ->execute([$status, $payment['job_id']]);
            
            // If successful, you could trigger additional actions here
            if ($resultCode == 0) {
                // Example: Send confirmation email or notification
            }
        }
        
        // Send response to M-Pesa
        echo json_encode([
            "ResultCode" => 0,
            "ResultDesc" => "Callback processed successfully"
        ]);
        
    } catch(Exception $e) {
        error_log("Callback processing failed: ".$e->getMessage());
        http_response_code(500);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid callback data"]);
}
?>