<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $config = new Config();
    $conn = $config->getConnection();
    
    echo json_encode(['success' => true, 'message' => 'Database connected successfully']);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
}
?>