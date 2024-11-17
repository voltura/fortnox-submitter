<?php
require_once 'authentication-check-no-redirect.php';

if (empty($user_id)) {
    header('Location: ../pages/login.php');
} else {
    header('Location: ../pages/submit.php');
}
?>
