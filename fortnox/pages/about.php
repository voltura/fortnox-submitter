<?php
require_once '../logic/authentication-check-no-redirect.php';
$isLoggedIn = !empty($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Fortnox Submitter</title>
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
    <?php if ($isLoggedIn): ?>
        <a href="submit.php" class="sidebar-link"><span class="link-text">Submit</span><i class="fas fa-upload"></i></a>
        <a href="documents.php" class="sidebar-link"><span class="link-text">Documents</span><i class="fas fa-folder"></i></a>
        <a href="edit-user.php" class="sidebar-link"><span class="link-text">Settings</span><i class="fas fa-cog"></i></a>
        <a href="../logic/logout.php" class="sidebar-link"><span class="link-text">Logout</span><i class="fas fa-sign-out-alt"></i></a>
    <?php else: ?>
        <a href="login.php" class="sidebar-link"><span class="link-text">Login</span><i class="fas fa-sign-in-alt"></i></a>
        <a href="register.php" class="sidebar-link"><span class="link-text">Register</span><i class="fas fa-user-plus"></i></a>
    <?php endif; ?>
    <a class="sidebar-link"><span class="link-text" style="text-decoration: underline;">About</span><i class="fas fa-info-circle"></i></a>
</div>


<div class="main-content">
    <div class="about-box">
        <img src="../images/fortnox.png" alt="Fortnox Submitter Logotype" class="about-icon">
        <p>Fortnox Submitter is a web application designed for easy submission and management of documents submitted to the Fortnox accounting system.</p>
        <p class="version"><br><strong>Version:</strong> 1.0.0</p>
        <footer class="footer">
            <p>&copy; <?php echo date('Y') ?> Voltura AB. All rights reserved.</p>
        </footer>
    </div>
</div>

<script src="../javascripts/theme.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
