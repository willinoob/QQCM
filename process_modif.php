<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['questions']) || !isset($_SESSION['id_t']) || !isset($_SESSION['index'])) {
    header("Location: start_quizz_modif.php");
    exit;
}

$id_t = (int) $_SESSION['id_t'];
$id_user = (int) $_SESSION['id_user'];

$sql_temps = "SELECT TIMESTAMPDIFF(SECOND, date, NOW()) AS temps_ecoule, etat_tentative
              FROM tentatives WHERE id_t = ? AND id_user = ?";
$stmt_temps = $conn->prepare($sql_temps);
$stmt_temps->bind_param("ii", $id_t, $id_user);
$stmt_temps->execute();
$resultat_temps = $stmt_temps->get_result();
$ligne_temps = $resultat_temps->fetch_assoc();
$stmt_temps->close();

if (!$ligne_temps) {
    die("Tentative introuvable ou non autorisée.");
}

if ($ligne_temps['etat_tentative'] === 'annulée') {
    session_destroy();
    header("Location: resultat_modif.php");
    exit;
}

$temps_ecoule = (int) $ligne_temps['temps_ecoule'];
$duree_maximale = 10 * 60;

if ($temps_ecoule > $duree_maximale) {
    $score_final = (int) $_SESSION['score'];

    $sql_fin = "UPDATE tentatives
                SET score = ?, temps_ecoule = ?, etat_tentative = 'terminée'
                WHERE id_t = ? AND id_user = ?";
    $stmt_fin = $conn->prepare($sql_fin);
    $stmt_fin->bind_param("iiii", $score_final, $temps_ecoule, $id_t, $id_user);
    $stmt_fin->execute();
    $stmt_fin->close();

    header("Location: resultat_modif.php");
    exit;
}

$index = (int) $_SESSION['index'];
$question = $_SESSION['questions'][$index];

$reponse_user = isset($_POST['reponse']) ? (int) $_POST['reponse'] : 0;
$bonne_reponse = (int) $question['bonne_reponse'];

if ($reponse_user === $bonne_reponse) {
    $_SESSION['score'] += 2;
}

$_SESSION['index']++;

if ($_SESSION['index'] >= 10) {
    $score_final = (int) $_SESSION['score'];

    $sql_fin = "UPDATE tentatives
                SET score = ?, temps_ecoule = ?, etat_tentative = 'terminée'
                WHERE id_t = ? AND id_user = ?";
    $stmt_fin = $conn->prepare($sql_fin);
    $stmt_fin->bind_param("iiii", $score_final, $temps_ecoule, $id_t, $id_user);
    $stmt_fin->execute();
    $stmt_fin->close();

    header("Location: resultat_modif.php");
    exit;
}

header("Location: quiz_modif.php");
exit;
?>