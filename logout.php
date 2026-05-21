<?php
require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

clear_remember_me_cookie();
session_unset();
session_destroy();

header('Location: login.php');
exit();
