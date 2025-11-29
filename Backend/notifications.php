<?php
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$config = new Config();
$conn = $config->getConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_notifications') {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        
        try {
            $query = "SELECT message, type, created_at, is_read 
                     FROM notifications 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id, $limit]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Mark as read
            $update_query = "UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([$user_id]);
            
            echo json_encode(['success' => true, 'notifications' => $notifications]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch notifications: ' . $e->getMessage()]);
        }
    }
}
?>