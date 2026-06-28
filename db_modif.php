<?php

$conn = mysqli_connect("localhost", "root", "root", "QQCM");

if ($conn) {
    echo "Connexion réussie !";
} else {
    die("Erreur : " . mysqli_connect_error());
}