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
    <title>Reset Password</title>
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
        &nbsp;<a class="link-text" href="login.php"><span class="link-text">Login</span></a>
        &nbsp;<i class="fas fa-sign-in-alt"></i>
        &nbsp;<i class="fas fa-angle-right"></i>
        &nbsp;<span class="link-text">Forgot Password?</span>
        &nbsp;<i class="fas fa-unlock-alt"></i>
    </span>
</div>

<div class="sidebar">
    <a href="login.php" class="sidebar-link"><span class="link-text">Login</span><i class="fas fa-sign-in-alt"></i></a>
    <a class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text underline">Forgot Password?</span><i class="fas fa-unlock-alt"></i></a>
    <a href="register.php" class="sidebar-link"><span class="link-text">Register</span><i class="fas fa-user-plus"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">
    
    <div class="error-message" id="errorMessage">Could not send Reset Password mail.</div>
    <div class="success-message" id="successMessage">Reset Password mail sent successfully.</div>

    <div class="send-reset-password-email-box">

        <h2>Reset Your Password</h2>

        <form id="send-reset-password-email-form" method="POST" autocomplete="off">

            <label for="your_email">Your Email:</label>
            <input type="email" name="your_email" id="your_email" required autocomplete="email">

            <label for="confirm_your_email">Confirm Your Email:</label>
            <input type="email" name="confirm_your_email" id="confirm_your_email" required autocomplete="off">

            <button id="submitButton" type="submit">Send Reset Link</button>
        </form>

    </div>

</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/send-reset-password-email.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
