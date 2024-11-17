<?php
require_once 'authentication-check-no-redirect.php';

header('Content-Type: application/json');

if (empty($user_id)) {
    echo json_encode(['status' => 'fatal', 'message' => 'User not logged in.']);
    exit;
}

try {

    $id = $_GET['id'];

    $stmt_restore_user_attachments_metadata = $pdo->prepare('
        UPDATE
            user_attachments_metadata
        SET
            deleted_at = NULL
        WHERE
            id = :id
            AND user_id = :user_id
    ');

    $stmt_restore_user_attachments_metadata->execute([
        'id' => $id, 
        'user_id' => $user_id
    ]);
    
    echo json_encode(['status' => 'success', 'message' => 'Document restored successfully.']);
    exit;

} catch (Exception) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to restore document.']);
    exit;
}
?>
