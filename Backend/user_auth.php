<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'];
    
    $config = new Config();
    $conn = $config->getConnection();
    
    if ($action === 'register') {
        $username = $data['username'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        try {
            // Insert user
            $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$username, $email, $password]);
            $user_id = $conn->lastInsertId();
            
            // Create default settings
            $settings_query = "INSERT INTO user_settings (user_id) VALUES (?)";
            $settings_stmt = $conn->prepare($settings_query);
            $settings_stmt->execute([$user_id]);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
            echo json_encode(['success' => true, 'message' => 'Registration successful']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
    }
    elseif ($action === 'login') {
        $username = $data['username'];
        $password = $data['password'];
        
        try {
            $query = "SELECT * FROM users WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                echo json_encode(['success' => true, 'message' => 'Login successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
        }
    }
    elseif ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }
}
?>