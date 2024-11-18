<?php
require_once '../logic/authentication-check-no-redirect.php';

$validate_account_token = $_GET['validate_account_token'];
$validate_account_time = null;
$hashed_token = null;

if (empty($validate_account_token)) {
    header('Location: login.php');
    exit;
}

try {
    list($user_id, $timestamp, $hashed_token) = explode(':', base64_decode($validate_account_token));

    if (empty($user_id)) {
        header('Location: login.php');
        exit;
    }
    
    $stmt_get_stored_validate_account_token_for_user = $pdo->prepare('
        SELECT
            validate_account_token
        FROM
            users
        WHERE
            id = :id
    ');

    if ($stmt_get_stored_validate_account_token_for_user->execute(['id' => $user_id])) {
        $stored_validate_account_token = $stmt_get_stored_validate_account_token_for_user->fetchColumn();

        if (empty($stored_validate_account_token)) {
            header('Location: login.php');
            exit;
        } else {
            list(, , $stored_hashed_token) = explode(':', base64_decode($stored_validate_account_token));
    
            if (!hash_equals($stored_hashed_token, $hashed_token)) {
                header('Location: login.php');
                exit;
            }
        }
    } else {
        header('Location: login.php');
        exit;
    }
} catch (Exception) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preload" href="../fonts/roboto/Roboto-Regular.woff2" as="font" type="font/woff2" crossorigin="anonymous">
    <link rel="preload" href="../fonts/roboto/Roboto-Bold.woff2" as="font" type="font/woff2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">
</head>
<body>

<div class="main-content">

    <div class="error-message" id="errorMessage">Account validation failed.</div>
    <div class="success-message" id="successMessage">Account successfully validated.</div>

    <div class="login-box">
        <h2>Account Validation</h2>
        <i class="centered fas fa-user-check"></i>
        <form id="validate-account-form" method="POST" autocomplete="off">
            <input type="hidden" id="validate_account_token" name="validate_account_token" value="<?php echo $validate_account_token; ?>">
        </form>
    </div>
    
</div>

<script src="../javascripts/validate-account.js"></script>

</body>
</html>
