<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['questions']) || !isset($_SESSION['id_t']) || !isset($_SESSION['id_user'])) {
    header("Location: start_quizz_modif.php");
    exit;
}

$id_t = (int) $_SESSION['id_t'];
$id_user = (int) $_SESSION['id_user'];
$questions = $_SESSION['questions'];

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

if ($ligne_temps['etat_tentative'] !== 'en_cours') {
    header("Location: resultat_modif.php");
    exit;
}

$temps_ecoule = (int) $ligne_temps['temps_ecoule'];

$reponses_user = isset($_POST['reponse']) ? $_POST['reponse'] : [];

$score = 0;

$sql_reponse = "INSERT INTO reponses (reponse_user, id_q, id_t) VALUES (?, ?, ?)";
$stmt_reponse = $conn->prepare($sql_reponse);

foreach ($questions as $q) {
    $id_q = (int) $q['id_q'];
    $bonne_reponse = (int) $q['bonne_reponse'];

    $reponse_donnee = isset($reponses_user[$id_q]) ? (int) $reponses_user[$id_q] : 0;

    if ($reponse_donnee === $bonne_reponse) {
        $score += 2;
    }

    $stmt_reponse->bind_param("iii", $reponse_donnee, $id_q, $id_t);
    $stmt_reponse->execute();
}

$stmt_reponse->close();

$sql_fin = "UPDATE tentatives
            SET score = ?, temps_ecoule = ?, etat_tentative = 'terminée', status = 'valide'
            WHERE id_t = ? AND id_user = ?";
$stmt_fin = $conn->prepare($sql_fin);
$stmt_fin->bind_param("iiii", $score, $temps_ecoule, $id_t, $id_user);
$stmt_fin->execute();
$stmt_fin->close();

$_SESSION['score'] = $score;

header("Location: resultat_modif.php");
exit;
?>