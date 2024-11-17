<?php
require_once 'authentication-check-no-redirect.php';

if (empty($user_id) || empty($_GET['id'])) {
    exit;
}

$attachment_id = $_GET['id'];

$stmt_get_user_attachment = $pdo->prepare('
    SELECT
        uam.file_name
        ,uam.file_type
        ,uad.file_data 
    FROM
        user_attachments_metadata AS uam
        JOIN user_attachments_data AS uad ON uam.id = uad.attachment_id
    WHERE
        uam.id = :attachment_id AND uam.user_id = :user_id
');

if (!$stmt_get_user_attachment->execute([
    'attachment_id' => $attachment_id,
    'user_id' => $user_id
])) {
    exit;
}

$attachment = $stmt_get_user_attachment->fetch(PDO::FETCH_ASSOC);

if ($attachment) {
    header('Content-Disposition: inline; filename="' . $attachment['file_name'] . '"');
    header('Content-Type: ' . $attachment['file_type']);
    echo $attachment['file_data'];
    exit;
}
?>
