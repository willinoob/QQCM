<?php
session_start();

$index = $_SESSION['index'];
$question = $_SESSION['qcm'][$index];

$reponse_user = $_POST['reponse'];
$bonne_reponse = $question['bonne_reponse'];

if ($reponse_user == $bonne_reponse) {
    $_SESSION['score']++;
}

$_SESSION['index']++;

// Fin du QCM
if ($_SESSION['index'] >= 10) {
    header("Location: resultat.php");
    exit;
}

header("Location: qcm.php");
exit;