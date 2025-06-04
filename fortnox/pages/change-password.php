<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    $redirect = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirect");
    exit;
}

require_once '../logic/authentication-check-no-redirect.php';

$reset_token = null;
$reset_time = null;
$hashed_token = null;

if (empty($user_id) && !empty($_GET['reset_token'])) {
    $reset_token = $_GET['reset_token'];

    list($user_id, $timestamp, $hashed_token) = explode(':', base64_decode($reset_token));

    if (empty($hashed_token) || empty($user_id) || time() - $timestamp > 900) {
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT
            reset_token
        FROM
            users
        WHERE
            id = :id
    ');
    $stmt->execute(['id' => $user_id]);
    $stored_reset_token = $stmt->fetchColumn();

    list(, , $stored_hashed_token) = explode(':', base64_decode($stored_reset_token));

    if (!hash_equals($stored_hashed_token, $hashed_token)) {
        header('Location: login.php');
        exit;
    }
}

if (empty($user_id)) {
    header('Location: login.php');
    exit;
}

$stmt_get_username = $pdo->prepare('
    SELECT
        username
    FROM
        users
    WHERE
        id = :id
');
$stmt_get_username->execute(['id' => $user_id]);
$username = $stmt_get_username->fetchColumn();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
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

<div class="hamburger-menu">
    <i class="fas fa-bars"></i>
    <span class="bread-crumbs">
        &nbsp;<a class="link-text" href="edit-user.php"><span class="link-text">Settings</span></a>
        &nbsp;<i class="fas fa-cog"></i>
        &nbsp;<i class="fas fa-angle-right"></i>
        &nbsp;<span class="link-text">Change Password</span>
        &nbsp;<i class="fas fa-key"></i>
    </span>
</div>

<div class="sidebar">
    <a href="submit.php" class="sidebar-link"><span class="link-text">Submit</span><i class="fas fa-upload"></i></a>
    <a href="documents.php" class="sidebar-link"><span class="link-text">Documents</span><i class="fas fa-folder"></i></a>
    <a href="edit-user.php" class="sidebar-link"><span class="link-text">Settings</span><i class="fas fa-cog"></i></a>
    <a class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text underline">Change Password</span><i class="fas fa-key"></i></a>
    <?php if (!$active_account): ?>
        <a href="activate.php" class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text">Activate Account</span><i class="fas fa-user-check"></i></a>
    <?php endif; ?>
    <a href="../logic/logout.php" class="sidebar-link"><span class="link-text">Logout</span><i class="fas fa-sign-out-alt"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">

    <div class="success-message" id="successMessage">Password updated successfully!</div>
    <div class="error-message" id="errorMessage">Failed to update password!</div>

    <div class="register-box">
        <h2>Change Password</h2>

        <form id="change-password-form" method="POST" autocomplete="off">
            <input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id; ?>">

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" readonly>

            <?php if ($reset_token): ?>
                <input type="hidden" id="reset_token" name="reset_token" value="<?php echo $reset_token; ?>">
            <? else: ?>
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"><br>
            <?php endif; ?>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required autocomplete="new-password"><br>

            <label for="confirm_new_password">Confirm New Password:</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" required autocomplete="new-password"><br>

            <button id="changePasswordButton" type="submit">Change Password</button>
        </form>

    </div>
    
</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/change-password.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
