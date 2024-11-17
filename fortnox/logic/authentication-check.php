<?php
session_start();

require_once 'db-connect.php';

$user_id = null;

try {
    if (!isset($_SESSION['loggedin']) || filter_var($_SESSION['loggedin'], FILTER_VALIDATE_BOOLEAN) !== true || empty($_SESSION['token'])) {
        header('Location: ../pages/login.php');
        exit;
    }
    
    $stmt_check_token = $pdo->prepare('
        SELECT
            user_id
        FROM
            session_tokens
        WHERE
            token = :token
        AND
            TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60
    ');
    
    $stmt_check_token->execute(['token' => $_SESSION['token']]);
    $user = $stmt_check_token->fetch(PDO::FETCH_ASSOC);
    
    if (empty($user)) {
        header('Location: ../pages/login.php');
        exit;
    }
    
    $user_id = $user['user_id'];
    
    if (empty($user_id)) {
        header('Location: ../pages/login.php');
        exit;
    }
    
    $stmt_refresh_token = $pdo->prepare('
        UPDATE
            session_tokens
        SET
            created_at = NOW()
        WHERE
            token = :token
    ');
    $stmt_refresh_token->execute(['token' => $_SESSION['token']]);
    
} catch (Exception) {
    header('Location: ../pages/login.php');
    exit;
}
?>
