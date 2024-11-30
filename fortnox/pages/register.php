<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    $redirect = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirect");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
    <a href="login.php" class="sidebar-link"><span class="link-text">Login</span><i class="fas fa-sign-in-alt"></i></a>
    <a class="sidebar-link"><span class="link-text underline">Register</span><i class="fas fa-user-plus"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">

    <div class="error-message hidden"></div>
    <div class="success-message hidden"></div>

    <div class="register-box">
        <h2>Register</h2>
        <form method="POST" autocomplete="off">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required autocomplete="off">
            
            <label for="password">Password:</label>
            <div class="tooltip">
                <input type="password" name="password" id="password" required autocomplete="new-password">
                <span class="tooltiptext">
                    Password must be at least 12 characters long, include at least one uppercase letter, one lowercase letter, and one number.
                </span>
            </div>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password">

            <label for="supplier_invoices_email">Supplier Invoices Email:</label>
            <input type="email" name="supplier_invoices_email" id="supplier_invoices_email" required>

            <label for="receipts_own_invoices_email">Receipts or Own Invoices Email:</label>
            <input type="email" name="receipts_own_invoices_email" id="receipts_own_invoices_email" required>

            <label for="your_email">Your Email:</label>
            <input type="email" name="your_email" id="your_email" required autocomplete="email">

            <label for="confirm_your_email">Confirm Your Email:</label>
            <input type="email" name="confirm_your_email" id="confirm_your_email" required autocomplete="off">

            <label for="test_mode">Test Mode:<br></label>
            <div class="tooltip">
                <label class="switch">
                    <input type="checkbox" name="test_mode" id="test_mode">
                    <span class="slider round"></span>
                </label>
                <span class="tooltiptext">
                    When Test Mode is enabled mails are only sent to your own email, not the <i>Supplier Invoices</i> or <i>Receipts or Own Invoices</i> email addresses.
                </span>
            </div>

            <button type="submit">Register</button>
        </form>
    </div>
    
</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/register.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
