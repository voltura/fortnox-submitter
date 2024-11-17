<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

require_once 'authentication-check-no-redirect.php';

header('Content-Type: application/json');

try {

    $reset_token = $_POST['reset_token'] ?? null;

    if (!$user_id && !$reset_token) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
        exit;
    }

    if ($reset_token) {
        list($user_id, $timestamp, $hashed_token) = explode(':', base64_decode($reset_token));
        if (time() - $timestamp > 900 || !$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
            exit;
        }
    }

    if (empty($user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if ($new_password !== $confirm_new_password) {
        echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
        exit;
    }

    $password_pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{12,}$/';
    
    if (!preg_match($password_pattern, $new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 12 characters long and include upper/lowercase letters and numbers.']);
        exit;
    }

    if ($current_password) {
        $stmt_get_user_password = $pdo->prepare('
            SELECT
                password
            FROM
                users
            WHERE
                id = :user_id
        ');
        $stmt_get_user_password->execute(['user_id' => $user_id]);
        $user = $stmt_get_user_password->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
            exit;
        }
    } else {
        $stmt_get_user_reset_token = $pdo->prepare('
            SELECT
                reset_token
            FROM
                users
            WHERE
                id = :user_id
        ');
        $stmt_get_user_reset_token->execute(['user_id' => $user_id]);
        $stored_reset_token = $stmt_get_user_reset_token->fetchColumn();
        list(, , $stored_hashed_token) = explode(':', base64_decode($stored_reset_token));

        if (time() - $timestamp > 900 || !hash_equals($stored_hashed_token, $hashed_token)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
            exit;
        }
    }

    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt_set_user_password = $pdo->prepare('
        UPDATE
            users
        SET
            password = :new_password
            ,reset_token = NULL
        WHERE
            id = :user_id
    ');
    
    if ($stmt_set_user_password->execute([
        'new_password' => $new_password_hash,
        'user_id' => $user_id
    ])) {
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }

} catch (Exception) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
}
?>
