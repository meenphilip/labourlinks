 <?php
require_once __DIR__.'/../includes/Database.php';
require_once __DIR__.'/../includes/Session.php';

header("Content-Type: application/json");

class AuthController {
    public static function signup() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            return;
        }

        $db = Database::getConnection();
        
        try {
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already registered']);
                return;
            }

            // Create user
            $stmt = $db->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->execute([
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT)
            ]);

            echo json_encode([
                'success' => true,
                'user_id' => $db->lastInsertId()
            ]);

        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
        }
    }

    public static function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            return;
        }

        $db = Database::getConnection();
        
        try {
            // Get user
            $stmt = $db->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($data['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }

            // Create session
            $session = Session::create($user['id']);
            
            echo json_encode([
                'success' => true,
                'session_id' => $session['id']
            ]);

        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
        }
    }
}

// Route the request
$action = $_GET['action'] ?? '';
switch ($action) {
    case 'signup':
        AuthController::signup();
        break;
    case 'login':
        AuthController::login();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
}
?>