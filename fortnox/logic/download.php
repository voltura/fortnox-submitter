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
        echo json_encode(['status' => 'error', 'message' => 'File not found.']);
        exit;
    }

    $stmt_get_document = $pdo->prepare('
        SELECT
            metadata.file_name
            ,metadata.file_type
            ,data.file_data
        FROM
            user_attachments_metadata AS metadata
            JOIN user_attachments_data AS data ON metadata.id = data.attachment_id
        WHERE
            metadata.id = :id
            AND metadata.user_id = :user_id
    ');
    $stmt_get_document->execute([
        'id' => $id,
        'user_id' => $user_id
    ]);

    $file = $stmt_get_document->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'File not found.']);
        exit;
    }

    header("Content-Description: File Transfer");
    header("Content-Type: " . $file['file_type']);
    header("Content-Disposition: attachment; filename=\"" . basename($file['file_name']) . "\"");
    header("Content-Length: " . strlen($file['file_data']));
    header("Cache-Control: must-revalidate");
    header("Pragma: public");

    echo $file['file_data'];
    exit;

} catch (Exception) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while fetching the file.']);
    exit;
}
?>
