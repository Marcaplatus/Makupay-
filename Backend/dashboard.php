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
    try {
        // Get dashboard data
        $stats_query = "
            SELECT 
                COUNT(*) as total_days,
                COALESCE(SUM(amount), 0) as total_amount,
                MAX(contribution_date) as last_contribution
            FROM contributions 
            WHERE user_id = ? AND status = 'completed'";
        $stats_stmt = $conn->prepare($stats_query);
        $stats_stmt->execute([$user_id]);
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get monthly breakdown
        $monthly_query = "
            SELECT 
                DATE_FORMAT(contribution_date, '%Y-%m') as month,
                SUM(amount) as monthly_total
            FROM contributions 
            WHERE user_id = ? AND status = 'completed'
            GROUP BY DATE_FORMAT(contribution_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6";
        $monthly_stmt = $conn->prepare($monthly_query);
        $monthly_stmt->execute([$user_id]);
        $monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'monthly_data' => $monthly_data
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch dashboard data: ' . $e->getMessage()]);
    }
}
?>