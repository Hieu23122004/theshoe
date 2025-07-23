<?php
session_start();
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user'])) {
        header("Location: ../../pages/login.php");
        exit();
    }

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        header("Location: ../../pages/login.php");
        exit();
    }

    return true;
}

checkAdminAuth();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();    
    session_destroy();   
    header("Location: ../../pages/login.php");
    exit();
}
$_SESSION['last_activity'] = time();
