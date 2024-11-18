<?php
require_once '../logic/authentication-check.php';

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

<div class="hamburger-menu">
    <i class="fas fa-bars"></i>
</div>

<div class="sidebar">
    <a href="submit.php" class="sidebar-link"><span class="link-text">Submit</span><i class="fas fa-upload"></i></a>
    <a href="documents.php" class="sidebar-link"><span class="link-text">Documents</span><i class="fas fa-folder"></i></a>
    <a class="sidebar-link"><span class="link-text" style="text-decoration: underline;">Settings</span><i class="fas fa-cog"></i></a>
    <a href="change-password.php" class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text">Change Password</span><i class="fas fa-key"></i></a>
    <?php if (!$active_account): ?>
        <a href="activate.php" class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text">Activate Account</span><i class="fas fa-user-check"></i></a>
    <?php endif; ?>
    <a href="../logic/logout.php" class="sidebar-link"><span class="link-text">Logout</span><i class="fas fa-sign-out-alt"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">

    <div class="success-message" id="successMessage">Settings updated successfully!</div>
    <div class="error-message" id="errorMessage">Failed to update settings!</div>

    <div class="register-box">
        <h2>Edit User Settings</h2>

        <form id="edit-user-form" action="../logic/edit-user-settings.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo $username ?? ''; ?>" autocomplete="username" readonly>
            
            <label for="supplier_invoices_email">Supplier Invoices Email:</label>
            <input type="email" id="supplier_invoices_email" name="supplier_invoices_email" value="<?php echo $user_settings['supplier_invoices_email'] ?? ''; ?>" required><br>
            <label for="receipts_own_invoices_email">Receipts or Own Invoices Email:</label>
            <input type="email" id="receipts_own_invoices_email" name="receipts_own_invoices_email" value="<?php echo $user_settings['receipts_own_invoices_email'] ?? ''; ?>" required><br>

            <label for="cc_email">CC Email:</label>
            <input type="email" id="cc_email" name="cc_email" value="<?php echo $user_settings['cc_email'] ?? ''; ?>" required><br>

            <label for="from_email">From Email:</label>
            <input type="email" id="from_email" name="from_email" value="<?php echo $user_settings['from_email'] ?? ''; ?>" required><br>

            <label for="test_mode">Test Mode (send only to CC mail):<br></label>
            <label class="switch">
                <input type="checkbox" name="test_mode" id="test_mode" <?php if ($user_settings['test_mode'] ?? '' == 'on') echo 'checked'; ?>>
                <span class="slider round"></span>
            </label>

            <button id="submitButton" type="submit">Update Settings</button>
            <div class="delete-section">
                <a href="#" class="delete-link" data-id="<?php echo $user_id; ?>">
                    <i class="fas fa-trash-alt"></i> Delete Account
                </a>
            </div>
        </form>
        
    </div>

    <div id="confirmationDialog" class="confirmationModal">
        <div class="modal-content">
            <h2>Confirm Delete User Account</h2>
            <p id="confirmationText">Are you sure you want to completely delete the account (cannot be restored)?</p>
            <div class="button-group">
                <button id="confirmDelete" class="cancel-button">Yes</button>
                <button id="cancelDelete" class="confirm-button">No</button>
            </div>
        </div>
    </div>

</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/edit-user.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
