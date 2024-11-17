<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid operation.']);
    exit;
}

require_once 'db-connect-no-redirect.php';

try {
    $validate_account_email_sent = false;
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $your_email = trim($_POST['your_email']);
    $supplier_invoices_email = trim($_POST['supplier_invoices_email']);
    $receipts_own_invoices_email = trim($_POST['receipts_own_invoices_email']);
    $test_mode = isset($_POST['test_mode']) && $_POST['test_mode'] === 'on' ? 'on' : 'off';

    $password_pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{12,}$/';

    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }

    if (!preg_match($password_pattern, $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 12 characters long and include upper/lowercase letters and numbers.']);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt_is_existing_user = $pdo->prepare('
        SELECT
            COUNT(*)
        FROM
            users
        WHERE
            username = :username
    ');
    $stmt_is_existing_user->execute(['username' => $username]);

    if ($stmt_is_existing_user->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username.']);
        exit;
    }

    $stmt_is_existing_email = $pdo->prepare('
        SELECT
            COUNT(*)
        FROM
            user_settings
        WHERE
            setting_value = :setting_value
            AND setting_key = :setting_key
    ');
    $stmt_is_existing_email->execute(['setting_value' => $your_email, 'setting_key' => 'from_email']);

    if ($stmt_is_existing_email->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already in use.']);
        exit;
    }
    
    $pdo->beginTransaction();

    $stmt_add_user = $pdo->prepare('
        INSERT INTO users
        (
            username
            ,password
        )
        VALUES
        (
            :username
            ,:password
        )
    ');

    if ($stmt_add_user->execute([
        'username' => $username,
        'password' => $password_hash
    ])) {
        $user_id = $pdo->lastInsertId();

        $settings = [
            'supplier_invoices_email' => $supplier_invoices_email,
            'receipts_own_invoices_email' => $receipts_own_invoices_email,
            'cc_email' => $your_email,
            'from_email' => $your_email,
            'test_mode' => $test_mode
        ];
        
        foreach ($settings as $key => $value) {
            $stmt_add_user_settings = $pdo->prepare('
                INSERT INTO user_settings 
                (
                    user_id
                    ,setting_key
                    ,setting_value
                ) 
                VALUES 
                (
                    :user_id
                    ,:key
                    ,:value
                )
            ');
            if (!$stmt_add_user_settings->execute([
                'user_id' => $user_id,
                'key' => $key,
                'value' => $value
            ])) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
                exit;
            }
        }
        $pdo->commit();

        include 'send-validate-account-email.php';

        if ($validate_account_email_sent) {
            echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
            exit;
        }
    }

    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
    exit;

} catch (Exception) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again later.']);
    exit;
}
?>
