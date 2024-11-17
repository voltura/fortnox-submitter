<?php
require_once '../config.php';

try {

    $pdo = new PDO($dsn, $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (Exception) {
    header('Location: ../pages/login.php');
    exit;
}
?>
