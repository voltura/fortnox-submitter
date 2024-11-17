<?php
session_start();
require_once 'db-connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $your_email = trim($_POST['your_email']);

    if (empty($your_email) || !filter_var($your_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Email not valid.']);
        exit;
    }

    try {
        $stmt_get_user_id_from_email = $pdo->prepare('
            SELECT
                u.id
            FROM
                user_settings us
                JOIN users u ON u.id = us.user_id
            WHERE
                us.setting_value = :setting_value
                AND us.setting_key = :setting_key
        ');

        if (!$stmt_get_user_id_from_email->execute([
            'setting_value' => $your_email,
            'setting_key' => 'from_email'
        ])) {
            echo json_encode(['status' => 'error', 'message' => 'Could not send password reset email.']);
            exit;
        }

        $users = $stmt_get_user_id_from_email->fetchAll(PDO::FETCH_COLUMN);

        if (count($users) !== 1) {
            echo json_encode(['status' => 'error', 'message' => 'Could not send password reset email.']);
            exit;
        }

        $user_id = $users[0];

        if (empty($user_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Could not send password reset email.']);
            exit;
        }

        $timestamp = time();
        $hashed_token = hash_hmac('sha256', "$user_id:$timestamp", $secret_key);
        $reset_token = base64_encode("$user_id:$timestamp:$hashed_token");
        
        $pdo->beginTransaction();

        $update_stmt = $pdo->prepare('
            UPDATE
                users
            SET
                reset_token = :reset_token
            WHERE
                id = :id
        ');

        if (!$update_stmt->execute([
            'reset_token' => $reset_token,
            'id' => $user_id
        ])) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
    
            echo json_encode(['status' => 'error', 'message' => 'Could not send password reset email.']);
            exit;
        }

        $base_url = 'https://voltura.se/fortnox/pages/change-password.php';
        $reset_url = "$base_url?reset_token=$reset_token";

        $to = $your_email;
        $from = 'no-reply@voltura.se';
        $subject = htmlspecialchars('Fortnox Submitter - Password Reset', ENT_QUOTES, 'UTF-8');

        $body = '
            <html>
            <head>
                <title>Fortnox Submitter - Password Reset</title>
            </head>
            <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Helvetica Neue, Arial, Helvetica, sans-serif; color: #333;" vlink="#ffffff">
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px; border: 0;">
                    <tr>
                        <td align="center" style="text-align: center;">
                            <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 20px; border: 1px solid #dddddd;">
                                <tr>
                                    <td align="center" style="padding: 20px; font-size: 24px; font-weight: bold; color: #333; text-align:center;">
                                        Fortnox Submitter - Password Reset
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding: 10px 20px; font-size: 16px; color: #333; text-align:center;">
                                        You are receiving this email because a request was made to reset your password for the Fortnox Submitter web application.
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding: 20px;">
                                        <table cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse; text-align: center;">
                                            <tr>
                                                <td align="center" style="background-color: #0056b3; border: 1px solid #0056b3; padding: 12px 24px;">
                                                    <a href="' . $reset_url . '" style="color: #ffffff; text-decoration: none; font-size: 16px; font-weight: bold; display: block;">
                                                        Reset Your Password
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding: 10px 20px; font-size: 16px; color: #333; text-align:center;">
                                        This link will expire in 15 minutes.
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding: 10px 20px; font-size: 16px; color: #333; text-align:center;">
                                        If you did not request a password reset, you can safely ignore this email.
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
        ';
                
        $headers = "From: " . $from . "\r\n";
        $headers .= "Reply-To: " . $from . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        
        if (mail($to, $subject, $body, $headers)) {
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Reset password email sent successfully.', 'reset_url' => $reset_url]);
            exit;
        }
        
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode(['status' => 'error', 'message' => 'Could not send password reset email.']);
        exit;
    } catch (Exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode(['status' => 'error', 'message' => 'Could not send password reset email.']);
        exit;
    }
}
?>
