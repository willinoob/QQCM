<?php

$conn = mysqli_connect("localhost", "root", "root", "QQCM");

if (!$conn) {
    die("Erreur : " . mysqli_connect_error());
}