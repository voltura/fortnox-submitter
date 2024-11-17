<?php
$validate_account_email_sent = false;

try {
    if (!empty($your_email) && filter_var($your_email, FILTER_VALIDATE_EMAIL) && !empty($user_id)) {
        $timestamp = time();
        $hashed_token = hash_hmac('sha256', "$user_id:$timestamp", $secret_key);
        $validate_account_token = base64_encode("$user_id:$timestamp:$hashed_token");

        $update_stmt = $pdo->prepare('
            UPDATE
                users
            SET
                validate_account_token = :validate_account_token
            WHERE
                id = :id
        ');

        if ($update_stmt->execute([
            'validate_account_token' => $validate_account_token,
            'id' => $user_id
        ])) {
            $base_url = 'https://voltura.se/fortnox/pages/validate-account.php';
            $validate_url = "$base_url?validate_account_token=$validate_account_token";

            $to = $your_email;
            $from = 'no-reply@voltura.se';
            $subject = htmlspecialchars('Fortnox Submitter - Account Validation', ENT_QUOTES, 'UTF-8');
            $body = '
                <html>
                <head>
                    <title>Fortnox Submitter - Validate Your Account</title>
                </head>
                <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Helvetica Neue, Arial, Helvetica, sans-serif; color: #333;" vlink="#ffffff">
                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px; border: 0;">
                        <tr>
                            <td align="center" style="text-align: center;">
                                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 20px; border: 1px solid #dddddd;">
                                    <tr>
                                        <td align="center" style="padding: 20px; font-size: 24px; font-weight: bold; color: #333; text-align:center;">
                                            Fortnox Submitter - Validate Your Account
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding: 10px 20px; font-size: 16px; color: #333; text-align:center;">
                                            You are receiving this email because an account was registered using this address on the Fortnox Submitter web application.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding: 20px;">
                                            <table cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse;text-align: center;">
                                                <tr>
                                                    <td align="center" style="background-color: #0056b3; border: 1px solid #0056b3; padding: 12px 24px;">
                                                        <a href="' . $validate_url . '" style="color: #ffffff; text-decoration: none; font-size: 16px; font-weight: bold; display: block;">
                                                            Validate Account
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding: 10px 20px; font-size: 16px; color: #333; text-align:center;">
                                            If you did not register for Fortnox Submitter, you can safely ignore this email.
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
                $validate_account_email_sent = true;
            }
        }
    }
} catch (Exception) {
}
?>
