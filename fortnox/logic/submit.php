<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

require_once 'authentication-check-no-redirect.php';

header('Content-Type: application/json');

if (empty($user_id)) {
    echo json_encode(['status' => 'fatal', 'message' => 'User not logged in.']);
    exit;
}

$validated_account_id = null;
$stmt_get_account_validated = $pdo->prepare('
    SELECT
        id
    FROM
        users
    WHERE
        id = :id
        AND validate_account_token IS NULL
');

if ($stmt_get_account_validated->execute(['id' => $user_id])) {
    $validated_account_id = $stmt_get_account_validated->fetchColumn();
}

if (empty($validated_account_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Account validation is required to proceed. Please check your email for the validation link.']);
    exit;
}

if (!isset($_FILES['attachment'])) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit document.']);
    exit;
}

$action = filter_var($_POST['action'], FILTER_VALIDATE_INT);

if ($action === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit document.']);
    exit;
}

$stmt_get_user_settings = $pdo->prepare('
    SELECT
        setting_key
        ,setting_value
    FROM
        user_settings
    WHERE
        user_id = :user_id
');

$stmt_get_user_settings->execute(['user_id' => $user_id]);
$settings = $stmt_get_user_settings->fetchAll(PDO::FETCH_ASSOC);
$user_settings = [];

foreach ($settings as $setting) {
    $user_settings[$setting['setting_key']] = $setting['setting_value'];
}

$test_mode = $user_settings['test_mode'] ?? 'on';
$cc = $user_settings['cc_email'] ?? '';
$from = $user_settings['from_email'] ?? '';

$supplier_invoices_email = ($test_mode == 'on') ? $cc : $user_settings['supplier_invoices_email'] ?? '';
$receipts_own_invoices_email = ($test_mode == 'on') ? $cc : $user_settings['receipts_own_invoices_email'] ?? '';

$to = ($_POST['action'] == 1) ? $receipts_own_invoices_email : $supplier_invoices_email;

if ($to == '' || $from == '') {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email.']);
    exit;
}

$subject = htmlspecialchars($_POST['subject'], ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');


$file_tmp_name = $_FILES['attachment']['tmp_name'];
$file_name = $_FILES['attachment']['name'];
$file_size = $_FILES['attachment']['size'];
$file_type = $_FILES['attachment']['type'];
$file_error = $_FILES['attachment']['error'];

$allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
$max_size = 25 * 1024 * 1024;

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF, JPG, and PNG files are allowed.']);
    exit;
}

if ($file_size > $max_size) {
    echo json_encode(['status' => 'error', 'message' => 'File is too large. Max allowed size is 25 MB.']);
    exit;
}

if ($file_error === UPLOAD_ERR_OK) {
    $file = fopen($file_tmp_name, "rb");
    $data = fread($file, filesize($file_tmp_name));
    fclose($file);
    $data = chunk_split(base64_encode($data));
    $boundary = md5(uniqid(time()));

    $headers = "From: " . $from . "\r\n";
    if ($cc != '') {
        $headers .= "CC: " . $cc . "\r\n";
    }
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= '
        <html>
        <head>
            <title>Fortnox Submitter - Document Submitted</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Helvetica Neue, Arial, Helvetica, sans-serif; color: #333;" vlink="#ffffff">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px; border: 0;">
                <tr>
                    <td align="center" style="text-align: center;">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 20px; border: 1px solid #dddddd;">
                            <tr>
                                <td align="center" style="padding: 20px; font-size: 24px; font-weight: bold; color: #333; text-align:center;">
                                    Fortnox Submitter - Document Submitted
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding: 10px 20px; font-size: 16px; color: #333; text-align:center;">
                                    You are receiving this email because a document was submitted via the Fortnox Submitter web application.
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding: 20px;">
                                    ' . $message . '
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding: 10px 20px; font-size: 16px; color: #333; text-align:center;">
                                    If you did not submit any document, please contact us at Fortnox Submitter so we can investigate why you received this email.
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding: 20px; font-size: 14px; color: #888; text-align: center;">
                                    &copy; ' . date('Y') . ' Voltura AB. All rights reserved.
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding: 10px; font-size: 14px; text-align: center;">
                                    <a href="https://www.voltura.se/fortnox" style="color: #007BFF; text-decoration: none;">Visit our website</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ' . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: {$file_type}; name=\"{$file_name}\"\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$file_name}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= $data . "\r\n";
    $body .= "--{$boundary}--";

    if (mail($to, $subject, $body, $headers)) {
        try {
            $file_data = file_get_contents($file_tmp_name);
            $sent_datetime = date('Y-m-d H:i:s');

            $pdo->beginTransaction();

            $stmt_metadata = $pdo->prepare('
                INSERT INTO user_attachments_metadata 
                (
                    user_id
                    ,file_name
                    ,file_type
                    ,attachment_size
                    ,sent_datetime
                    ,sent_to
                )
                VALUES 
                (
                    :user_id
                    ,:file_name
                    ,:file_type
                    ,:attachment_size
                    ,:sent_datetime
                    ,:sent_to
                )
            ');

            if ($stmt_metadata->execute([
                'user_id' => $user_id,
                'file_name' => $file_name,
                'file_type' => $file_type,
                'attachment_size' => $file_size,
                'sent_datetime' => $sent_datetime,
                'sent_to' => $to
            ])) {
                $attachment_id = $pdo->lastInsertId();

                $stmt_data = $pdo->prepare('
                    INSERT INTO user_attachments_data 
                    (
                        attachment_id
                        ,file_data
                    )
                    VALUES 
                    (
                        :attachment_id
                        ,:file_data
                    )
                ');

                if ($stmt_data->execute([
                    'attachment_id' => $attachment_id,
                    'file_data' => $file_data
                ])) {
                    $pdo->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Document submitted successfully.', 'stored_in_database' => true]);
                    exit;
                }
            }
            
            $pdo->rollBack();
        } catch (Exception) {
            $pdo->rollBack();
        }

        echo json_encode(['status' => 'success', 'message' => 'Document submitted successfully.', 'stored_in_database' => false]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit document.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit document.']);
    exit;
}
?>
