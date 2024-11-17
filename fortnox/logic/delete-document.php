<?php
require_once 'authentication-check-no-redirect.php';

header('Content-Type: application/json');

if (empty($user_id)) {
    echo json_encode(['status' => 'fatal', 'message' => 'User not logged in.']);
    exit;
}

$permanent = false;

try {
    $id = $_GET['id'];

    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete document.']);
        exit;
    }
    
    $permanent = isset($_GET['permanent']) ? filter_var($_GET['permanent'], FILTER_VALIDATE_BOOLEAN) : false;

    if ($permanent) {
        $pdo->beginTransaction();

        $stmt_delete_user_attachments_data = $pdo->prepare('
            DELETE
                user_attachments_data
            FROM
                user_attachments_data
                INNER JOIN user_attachments_metadata ON user_attachments_data.attachment_id = user_attachments_metadata.id
            WHERE
                user_attachments_metadata.id = :id
                AND user_attachments_metadata.user_id = :user_id
        ');

        $stmt_delete_user_attachments_metadata = $pdo->prepare('
            DELETE FROM
                user_attachments_metadata
            WHERE
                id = :id
                AND user_id = :user_id
        ');

        if ($stmt_delete_user_attachments_data->execute([
            'id' => $id,
            'user_id' => $user_id
        ])) {
            if ($stmt_delete_user_attachments_metadata->execute([
                'id' => $id,
                'user_id' => $user_id
            ])) {
                $pdo->commit(); 
                echo json_encode(['status' => 'success', 'message' => 'Document deleted successfully.']);
                exit;
            } else {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete document.']);
                exit;
            }
        } else {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete document.']);
            exit;
        }
    } else {
        $stmt_archive_user_attachments_metadata = $pdo->prepare('
            UPDATE
                user_attachments_metadata
            SET
                deleted_at = NOW()
            WHERE
                id = :id
                AND user_id = :user_id
        ');
        if ($stmt_archive_user_attachments_metadata->execute([
            'id' => $id,
            'user_id' => $user_id
        ])) {
            echo json_encode(['status' => 'success', 'message' => 'Document archived successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to archive document.']);
        }
    }
    exit;
} catch (Exception) {
    if ($permanent) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete document.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to archive document.']);
    }
    exit;
}
?>
