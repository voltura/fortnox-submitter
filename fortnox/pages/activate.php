<?php
require_once '../logic/authentication-check.php';

$stmt_get_user_email = $pdo->prepare('
    SELECT
        setting_value
    FROM
        user_settings
    WHERE
        user_id = :user_id
        AND setting_key = :setting_key
');

$stmt_get_user_email->execute([
    'user_id' => $user_id,
    'setting_key' => 'from_email'
]);

$your_email = $stmt_get_user_email->fetchColumn();

$stmt_user_account_activated = $pdo->prepare('
    SELECT
        COUNT(*)
    FROM
        users
    WHERE
        id = :id
        AND validate_account_token IS NULL
');

$stmt_user_account_activated->execute(['id' => $user_id]);
$active_account = $stmt_user_account_activated->fetchColumn() > 0;

if ($active_account) {
    header('Location: edit-user.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Settings</title>
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

<button id="themeToggle">
    <i class="fas fa-moon"></i>
</button>

<div class="sidebar">
    <a href="submit.php" class="sidebar-link"><span class="link-text">Submit</span><i class="fas fa-upload"></i></a>
    <a href="documents.php" class="sidebar-link"><span class="link-text">Documents</span><i class="fas fa-folder"></i></a>
    <a href="edit-user.php" class="sidebar-link"><span class="link-text">Settings</span><i class="fas fa-cog"></i></a>
    <a href="change-password.php" class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text">Change Password</span><i class="fas fa-key"></i></a>
    <?php if (!$active_account): ?>
        <a class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text" style="text-decoration: underline;">Activate Account</span><i class="fas fa-user-check"></i></a>
    <?php endif; ?>
    <a href="../logic/logout.php" class="sidebar-link"><span class="link-text">Logout</span><i class="fas fa-sign-out-alt"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">

    <div class="success-message" id="successMessage">Account activation email sent successfully.</div>
    <div class="error-message" id="errorMessage">Failed to send account activation email.</div>

    <div class="register-box">
        <h2>Activate Account</h2>

        <form id="edit-user-form" method="POST">
            <input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id; ?>">
            <label for="your_email">Your Email:</label>
            <input type="email" id="your_email" name="your_email" value="<?php echo $your_email ?? ''; ?>" readonly required><br>
            <button id="submitButton" type="submit">Send Account Activation Email</button>
        </form>
        
    </div>

</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/activate.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
