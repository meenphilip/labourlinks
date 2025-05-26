<?php
require_once 'database.php';

header("Content-Type: application/json");

// Connect to MySQL on port 3306
$db = Database::getConnection();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['email']) || empty($data['password']) || empty($data['user_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email, password and user type required']);
    exit;
}

try {
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Hash password and create user
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (email, password, user_type, name, phone, county, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $data['email'], 
        $hashedPassword, 
        $data['user_type'],
        $data['name'] ?? '',
        $data['phone'] ?? '',
        $data['county'] ?? ''
    ]);

    // Return success
    echo json_encode([
        'success' => true,
        'user_id' => $db->lastInsertId()
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
}
?>