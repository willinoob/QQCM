<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) && isset($_SESSION['user_id'])) {
    $_SESSION['id_user'] = (int) $_SESSION['user_id'];
}

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../connexion.php');
    exit;
}
