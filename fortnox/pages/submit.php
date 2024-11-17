<?php
require_once '../logic/authentication-check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit to Fortnox</title>
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
    <a class="sidebar-link"><span class="link-text" style="text-decoration: underline;">Submit</span><i class="fas fa-upload"></i></a>
    <a href="documents.php" class="sidebar-link"><span class="link-text">Documents</span><i class="fas fa-folder"></i></a>
    <a href="edit-user.php" class="sidebar-link"><span class="link-text">Settings</span><i class="fas fa-cog"></i></a>
    <a href="../logic/logout.php" class="sidebar-link"><span class="link-text">Logout</span><i class="fas fa-sign-out-alt"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">

    <div class="success-message" id="successMessage">Document submitted successfully.</div>
    <div class="error-message" id="errorMessage">Failed to submit document.</div>

    <div class="email-form">
        <h2>Submit to Fortnox</h2>
        <form id="emailForm" enctype="multipart/form-data" method="POST">
            <input type="hidden" name="action" value="1">

            <input type="text" id="subject" name="subject" placeholder="Document to Fortnox" hidden>
            <textarea id="message" name="message" placeholder="See attachment" rows="1" style="visibility: hidden;position: absolute;"></textarea>

            <div class="drop-zone-container">
                <div class="drop-zone" id="dropZoneReceipt">
                    <i class="fas fa-receipt" style="font-size: 24px; margin-bottom: 8px;"></i>
                    <p><b>Receipt or own invoice</b></p>
                    <p>Drag and drop a file here, or click to select one</p>
                    <input type="file" name="attachment" id="fileInputReceipt" hidden>
                </div>

                <div class="drop-zone" id="dropZoneSupplierInvoice">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 24px; margin-bottom: 8px;"></i>
                    <p><b>Supplier invoice</b></p>
                    <p>Drag and drop a file here, or click to select one</p>
                    <input type="file" name="attachment" id="fileInputSupplierInvoice" hidden>
                </div>
            </div>

            <button id="submitButton" type="submit">Submit</button>
        </form>
    </div>
    
</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/submit.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
