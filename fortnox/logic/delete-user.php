<?php
require_once 'authentication-check-no-redirect.php';

header('Content-Type: application/json');

if (empty($user_id)) {
    echo json_encode(['status' => 'fatal', 'message' => 'User not logged in.']);
    exit;
}

try {

    $id = $_GET['id'];

    if (empty($id)) {
        echo json_encode(['status' => 'fatal', 'message' => 'Failed to delete user account.']);
        exit;
    }

    if ($id !== $user_id) {
        echo json_encode(['status' => 'fatal', 'message' => 'Failed to delete user account.']);
        exit();
    }

    $stmt_delete_user_attachements_data = $pdo->prepare('
        DELETE
            user_attachments_data
        FROM
            user_attachments_data
            JOIN user_attachments_metadata ON user_attachments_data.attachment_id = user_attachments_metadata.id
        WHERE
            user_attachments_metadata.user_id = :user_id
    ');

    $stmt_delete_user_attachments_metadata = $pdo->prepare('
        DELETE FROM
            user_attachments_metadata
        WHERE
            user_id = :user_id
    ');

    $stmt_delete_user_settings = $pdo->prepare('
        DELETE FROM
            user_settings
        WHERE
            user_id = :user_id
    ');

    $stmt_delete_user = $pdo->prepare('
        DELETE FROM
            users
        WHERE
            id = :id
    ');

    $stmt_delete_user_attachments_data->execute(['user_id' => $user_id]);
    $stmt_delete_user_attachments_metadata->execute(['user_id' => $user_id]);
    $stmt_delete_user_settings->execute(['user_id' => $user_id]);

    if ($stmt_delete_user->execute(['id' => $user_id])) {
        echo json_encode(['status' => 'success', 'message' => 'User account deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user account.']);
    }
    exit;

} catch (Exception) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete user account.']);
    exit;
}
?>
