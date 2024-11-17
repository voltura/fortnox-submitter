<?php
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['token'])) {
    phpinfo();
} else {
    header('Location: pages/login.php');
    exit;
}
?>
