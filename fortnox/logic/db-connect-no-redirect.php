<?php
require_once '../config.php';

try {

    $pdo = new PDO($dsn, $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (Exception) {
    echo json_encode(['status' => 'fatal', 'message' => 'Database connect issue.']);
    exit;
}
?>
