<?php
$conn = mysqli_connect("localhost", "root", "", "qqcm");

if (!$conn) {
    die("Erreur de connexion à la base : " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>