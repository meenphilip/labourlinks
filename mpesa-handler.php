
<?php
require_once 'db_connection.php';
require_once 'config.php';

// Generate access token
function getAccessToken() {
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode(MPESA_CONSUMER_KEY.':'.MPESA_CONSUMER_SECRET);
    
    $headers = ['Authorization: Basic '.$credentials];
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response)->access_token;
}

// Process job submission with payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // First save job with pending payment status
        $stmt = $conn->prepare("INSERT INTO jobs (
            title, job_type, county, location, description, salary, start_date, duration,
            experience, certifications, requirements, company_name, industry, company_description,
            contact_name, contact_phone, contact_email, contact_method, is_featured, is_urgent,
            payment_status, payment_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 100.00)");
        
        $stmt->execute([
            $data['title'], $data['job_type'], $data['county'], $data['location'], $data['description'],
            $data['salary'], $data['start_date'], $data['duration'], $data['experience'],
            $data['certifications'], $data['requirements'], $data['company_name'], $data['industry'],
            $data['company_description'], $data['contact_name'], $data['contact_phone'],
            $data['contact_email'], $data['contact_method'], $data['is_featured'], $data['is_urgent']
        ]);
        
        $jobId = $conn->lastInsertId();
        
        // Initiate M-Pesa payment
        $phone = preg_replace('/^0/', '254', $data['contact_phone']); // Format to 254...
        $timestamp = date('YmdHis');
        $password = base64_encode(MPESA_SHORT_CODE.MPESA_PASSKEY.$timestamp);
        
        $curl_post_data = [
            'BusinessShortCode' => MPESA_SHORT_CODE,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => '100',
            'PartyA' => $phone,
            'PartyB' => MPESA_SHORT_CODE,
            'PhoneNumber' => $phone,
            'CallBackURL' => CALLBACK_URL,
            'AccountReference' => 'JobPosting-'.$jobId,
            'TransactionDesc' => 'LabourLinks Job Posting Fee'
        ];
        
        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.getAccessToken(),
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($curl_post_data)
        ]);
        
        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        
        if ($response->ResponseCode == '0') {
            // Save payment record
            $stmt = $conn->prepare("INSERT INTO payments (
                job_id, mpesa_request_id, mpesa_checkout_id, phone_number, amount
            ) VALUES (?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $jobId, $response->MerchantRequestID, $response->CheckoutRequestID, 
                $phone, 100.00
            ]);
            
            http_response_code(200);
            echo json_encode([
                'status' => 'processing',
                'message' => 'Payment initiated. Complete the prompt on your phone.',
                'job_id' => $jobId
            ]);
        } else {
            throw new Exception('Failed to initiate payment: '.$response->errorMessage);
        }
        
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>