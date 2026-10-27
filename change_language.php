<?php
session_start();
if (isset($_GET['lang'])) {
    $allowed_langs = ['en', 'hi', 'gu'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: $redirect");
exit;
?>
