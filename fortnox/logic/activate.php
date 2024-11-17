<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid operation.']);
    exit;
}

require_once 'db-connect-no-redirect.php';

$validate_account_email_sent = false;

try {
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);

    if (empty($user_id)) {
        echo json_encode(['status' => 'fatal', 'message' => 'User not logged in.']);
        exit;
    }

    $your_email = trim($_POST['your_email']);

    if (empty($your_email)) {
        echo json_encode(['status' => 'fatal', 'message' => 'Email not supplied.']);
        exit;
    }

    include 'send-validate-account-email.php';

    if ($validate_account_email_sent) {
        echo json_encode(['status' => 'success', 'message' => 'Account Activatation Email Sent Successfully.']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed To Send Account Activatation Email.']);
        exit;
    }
} catch (Exception) {
    echo json_encode(['status' => 'error', 'message' => 'Failed To Send Account Activatation Email.']);
    exit;
}
exit;
?>
