<?php
require_once 'database.php';

header("Content-Type: application/json");

// Connect to MySQL on port 3306
$db = Database::getConnection();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['email']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password required']);
    exit;
}

try {
    // Find user by email
    $stmt = $db->prepare("SELECT id, password, user_type FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    // Verify password
    if (password_verify($data['password'], $user['password'])) {
        echo json_encode([
            'success' => true,
            'user_id' => $user['id'],
            'user_type' => $user['user_type']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
}
?>