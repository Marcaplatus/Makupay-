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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'];
    
    if ($action === 'make_contribution') {
        $amount = $data['amount'];
        $date = date('Y-m-d');
        
        try {
            // Check if contribution already made today
            $check_query = "SELECT id FROM contributions WHERE user_id = ? AND contribution_date = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$user_id, $date]);
            
            if ($check_stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Contribution already made today']);
                return;
            }
            
            // Insert contribution
            $query = "INSERT INTO contributions (user_id, amount, contribution_date, status) VALUES (?, ?, ?, 'completed')";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id, $amount, $date]);
            
            // Add notification
            $notification_msg = "Daily contribution of $$amount completed successfully!";
            $notif_query = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')";
            $notif_stmt = $conn->prepare($notif_query);
            $notif_stmt->execute([$user_id, $notification_msg]);
            
            echo json_encode(['success' => true, 'message' => 'Contribution recorded successfully']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Contribution failed: ' . $e->getMessage()]);
        }
    }
    elseif ($action === 'process_auto_contribution') {
        // This would be called by a cron job
        $date = date('Y-m-d');
        
        try {
            // Get users with auto-contribution enabled
            $users_query = "SELECT us.user_id, us.daily_amount FROM user_settings us WHERE us.auto_contribute = TRUE";
            $users_stmt = $conn->prepare($users_query);
            $users_stmt->execute();
            $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            foreach ($users as $user) {
                // Check if contribution already made today
                $check_query = "SELECT id FROM contributions WHERE user_id = ? AND contribution_date = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->execute([$user['user_id'], $date]);
                
                if ($check_stmt->rowCount() === 0) {
                    // Make automatic contribution
                    $contrib_query = "INSERT INTO contributions (user_id, amount, contribution_date, status) VALUES (?, ?, ?, 'completed')";
                    $contrib_stmt = $conn->prepare($contrib_query);
                    $contrib_stmt->execute([$user['user_id'], $user['daily_amount'], $date]);
                    
                    // Add notification
                    $notification_msg = "Automatic daily contribution of $" . $user['daily_amount'] . " completed!";
                    $notif_query = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')";
                    $notif_stmt = $conn->prepare($notif_query);
                    $notif_stmt->execute([$user['user_id'], $notification_msg]);
                    
                    $results[] = "User {$user['user_id']}: $$user[daily_amount] contributed";
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Auto-contributions processed', 'results' => $results]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Auto-contribution processing failed: ' . $e->getMessage()]);
        }
    }
    elseif ($action === 'update_settings') {
        $daily_amount = $data['daily_amount'];
        $auto_contribute = $data['auto_contribute'];
        
        try {
            $query = "UPDATE user_settings SET daily_amount = ?, auto_contribute = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$daily_amount, $auto_contribute, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Settings update failed: ' . $e->getMessage()]);
        }
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_stats') {
        try {
            // Get total contributions
            $total_query = "SELECT COALESCE(SUM(amount), 0) as total FROM contributions WHERE user_id = ? AND status = 'completed'";
            $total_stmt = $conn->prepare($total_query);
            $total_stmt->execute([$user_id]);
            $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get current streak (consecutive days with contributions)
            $streak_query = "
                SELECT COUNT(*) as streak 
                FROM (
                    SELECT contribution_date 
                    FROM contributions 
                    WHERE user_id = ? AND status = 'completed' 
                    GROUP BY contribution_date 
                    ORDER BY contribution_date DESC
                ) dates";
            $streak_stmt = $conn->prepare($streak_query);
            $streak_stmt->execute([$user_id]);
            $streak = $streak_stmt->fetch(PDO::FETCH_ASSOC)['streak'];
            
            // Get today's contribution status
            $today_query = "SELECT id FROM contributions WHERE user_id = ? AND contribution_date = CURDATE()";
            $today_stmt = $conn->prepare($today_query);
            $today_stmt->execute([$user_id]);
            $today_contributed = $today_stmt->rowCount() > 0;
            
            // Get user settings
            $settings_query = "SELECT daily_amount, auto_contribute FROM user_settings WHERE user_id = ?";
            $settings_stmt = $conn->prepare($settings_query);
            $settings_stmt->execute([$user_id]);
            $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'total_contributions' => $total,
                'current_streak' => $streak,
                'today_contributed' => $today_contributed,
                'settings' => $settings
            ]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch stats: ' . $e->getMessage()]);
        }
    }
    elseif (isset($_GET['action']) && $_GET['action'] === 'get_history') {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        
        try {
            $query = "SELECT amount, contribution_date, status, created_at 
                     FROM contributions 
                     WHERE user_id = ? 
                     ORDER BY contribution_date DESC 
                     LIMIT ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id, $limit]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'history' => $history]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch history: ' . $e->getMessage()]);
        }
    }
}
?>