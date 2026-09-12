<?php
require_once __DIR__ . '/../config/functions.php';
if(isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $conn->query("UPDATE notifikasi SET is_read=1 WHERE user_id=$uid");
}
$back = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: $back");
exit;
