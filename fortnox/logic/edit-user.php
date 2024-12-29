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

try {
    $supplier_invoices_email = $_POST['supplier_invoices_email'];
    $receipts_own_invoices_email = $_POST['receipts_own_invoices_email'];
    $cc_email = $_POST['cc_email'];
    $from_email = $_POST['from_email'];
    $test_mode = isset($_POST['test_mode']) ? 'on' : 'off';

    if (
        !isset($user_id, $supplier_invoices_email, $receipts_own_invoices_email, $cc_email, $from_email) 
        || empty($user_id) 
        || empty($supplier_invoices_email) 
        || empty($receipts_own_invoices_email) 
        || empty($cc_email) 
        || empty($from_email)
    ) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update settings.']);
        exit;
    }

    $stmt_is_taken_email = $pdo->prepare('
        SELECT
            COUNT(*)
        FROM
            user_settings
        WHERE
            setting_value = :setting_value
            AND setting_key = :setting_key
            AND user_id != :user_id
    ');
    $stmt_is_taken_email->execute([
        'setting_value' => $your_email,
        'setting_key' => 'from_email',
        'user_id' => $user_id
    ]);

    if ($stmt_is_taken_email->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update settings.']);
        exit;
    }

    $stmt_preexisting_from_email = $pdo->prepare('
        SELECT
            setting_value
        FROM
            user_settings
        WHERE
            setting_key = :setting_key
            AND user_id = :user_id
    ');
    $stmt_preexisting_from_email->execute([
        'setting_key' => 'from_email',
        'user_id' => $user_id
    ]);

    $preexisting_from_email = $stmt_preexisting_from_email->fetchColumn();

    $pdo->beginTransaction();

    $query_update_user_settings = $pdo->prepare('
        UPDATE
            user_settings
        SET
            setting_value = CASE 
                WHEN setting_key = "supplier_invoices_email" THEN :supplier_invoices_email
                WHEN setting_key = "receipts_own_invoices_email" THEN :receipts_own_invoices_email
                WHEN setting_key = "cc_email" THEN :cc_email
                WHEN setting_key = "from_email" THEN :from_email
                WHEN setting_key = "test_mode" THEN :test_mode
                ELSE setting_value
            END
        WHERE
            user_id = :user_id
    ');

    if ($query_update_user_settings->execute([
        ':supplier_invoices_email' => $supplier_invoices_email,
        ':receipts_own_invoices_email' => $receipts_own_invoices_email,
        ':cc_email' => $cc_email,
        ':from_email' => $from_email,
        ':test_mode' => $test_mode,
        ':user_id' => $user_id
    ])) {

        if ($pdo->inTransaction()) {
            $pdo->commit();
        }

        if ($preexisting_from_email !== $from_email) {
            $validate_account_email_sent = false;
            $your_email = $from_email;
            
            include 'send-validate-account-email.php';

            if ($validate_account_email_sent === true) {
                echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully.']);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Settings were updated successfully, but the account validation email could not be sent. The account remains inactive.']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully.']);
            exit;
        }
        exit;
    } else {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode(['status' => 'error', 'message' => 'Failed to update settings.']);
        exit;
    }
    exit;

} catch (Exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Failed to update settings.']);
    exit;
}
?>
