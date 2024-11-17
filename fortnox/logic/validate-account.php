<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

require_once 'authentication-check-no-redirect.php';

header('Content-Type: application/json');

function sendErrorResponse($message = 'Account validation failed.') {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

try {
    $validate_account_token = $_POST['validate_account_token'];

    if (empty($validate_account_token)) {
        sendErrorResponse();
    }

    list($user_id, $timestamp, $hashed_token) = explode(':', base64_decode($validate_account_token));

    if (empty($user_id) || empty($hashed_token) || empty($timestamp)) {
        sendErrorResponse();
    }

    $stmt_get_stored_validate_account_token_for_user = $pdo->prepare('
        SELECT
            validate_account_token
        FROM
            users
        WHERE
            id = :user_id
    ');

    if ($stmt_get_stored_validate_account_token_for_user->execute(['user_id' => $user_id])) {
        $stored_validate_account_token = $stmt_get_stored_validate_account_token_for_user->fetchColumn();

        if (empty($stored_validate_account_token)) {
            echo json_encode(['status' => 'success', 'message' => 'Account successfully validated.']);
            exit;
        }

        list(, , $stored_hashed_token) = explode(':', base64_decode($stored_validate_account_token));

        if (hash_equals($stored_hashed_token, $hashed_token)) {
            $stmt_remove_validate_account_token = $pdo->prepare('
                UPDATE
                    users
                SET
                    validate_account_token = NULL
                WHERE
                    id = :id
            ');

            if ($stmt_remove_validate_account_token->execute(['id' => $user_id])) {
                echo json_encode(['status' => 'success', 'message' => 'Account successfully validated.']);
                exit;
            } else {
                sendErrorResponse();
            }
        }
    } else {
        sendErrorResponse();
    }
} catch (Exception) {
    sendErrorResponse();
}
?>
