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
    <title>Payment Details</title>
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
    <a class="sidebar-link"><span class="link-text underline">Payment Details</span><i class="fas fa-file-invoice-dollar"></i></a>
    <a href="edit-user.php" class="sidebar-link"><span class="link-text">Settings</span><i class="fas fa-cog"></i></a>
    <a href="../logic/logout.php" class="sidebar-link"><span class="link-text">Logout</span><i class="fas fa-sign-out-alt"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">

    <div class="success-message" id="successMessage">Payment details extracted.</div>
    <div class="error-message" id="errorMessage">Failed to extract payment details.</div>

    <div class="payment-reader-box">
        <h2>Payment Details</h2>
        <form id="paymentDetailsForm" enctype="multipart/form-data" method="POST">
            <div class="drop-zone payment-drop-zone" id="paymentDropZone">
                <i class="fas fa-file-pdf drop-font"></i>
                <p><b>Supplier invoice PDF</b></p>
                <p>Drag and drop a PDF here, or click to select one</p>
                <input type="file" name="payment_pdf" id="paymentPdfInput" accept="application/pdf,.pdf" hidden>
            </div>

            <button id="extractButton" type="submit" disabled>Read Payment Details</button>
        </form>

        <div id="paymentResults" class="payment-results hidden">
            <div class="payment-field">
                <label for="paymentAmount">Amount</label>
                <div class="payment-copy-row">
                    <input type="text" id="paymentAmount" readonly>
                    <button type="button" class="payment-copy-button" data-copy-target="paymentAmount" title="Copy amount">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="payment-field">
                <label for="paymentOcr">OCR number</label>
                <div class="payment-copy-row">
                    <input type="text" id="paymentOcr" readonly>
                    <button type="button" class="payment-copy-button" data-copy-target="paymentOcr" title="Copy OCR number">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="payment-field">
                <label for="paymentAccount">Account</label>
                <div class="payment-copy-row">
                    <input type="text" id="paymentAccount" readonly>
                    <button type="button" class="payment-copy-button" data-copy-target="paymentAccount" title="Copy account">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <details class="payment-text-preview">
                <summary>Extracted text</summary>
                <textarea id="paymentTextPreview" readonly></textarea>
            </details>
        </div>
    </div>

</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/payment-details.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
