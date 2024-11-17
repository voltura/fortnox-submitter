<?php
session_start();

require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

header('Content-Type: application/json');

try {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Login failed.']);
        exit;
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Login failed.']);
        exit;
    }

    $stmt_get_user_id_and_password = $pdo->prepare('
        SELECT
            id
            ,password
        FROM
            users
        WHERE
            username = :username
    ');

    if ($stmt_get_user_id_and_password->execute([':username' => $username])) {
        $user = $stmt_get_user_id_and_password->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $secureToken = bin2hex(random_bytes(32));

            $stmt_insert_token = $pdo->prepare('
                INSERT INTO session_tokens
                (
                    token
                    ,user_id
                    ,created_at
                )
                VALUES
                (
                    :token
                    ,:user_id
                    ,NOW()
                )
            ');
            
            if ($stmt_insert_token->execute([
                'token' => $secureToken,
                'user_id' => $user['id']
            ])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['token'] = $secureToken;

                try {
                    $stmt_delete_old_user_tokens = $pdo->prepare('
                        DELETE FROM session_tokens
                        WHERE
                            token != :token
                            AND user_id = :user_id
                    ');

                    $stmt_delete_old_user_tokens->execute([
                        'token' => $secureToken,
                        'user_id' => $user['id']
                    ]);
                } catch (Exception) {
                }
 
                echo json_encode(['status' => 'success', 'message' => 'Login successful.']);
                exit;
            }
        }
    }

} catch (Exception) {
}

echo json_encode(['status' => 'error', 'message' => 'Login failed.']);
?>
