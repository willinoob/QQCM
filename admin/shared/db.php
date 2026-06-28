<?php
$conn = mysqli_connect('localhost', 'root', '', 'QQCM');

if (!$conn) {
    die('Impossible de se connecter a la base de donnees.');
}

mysqli_set_charset($conn, 'utf8mb4');
