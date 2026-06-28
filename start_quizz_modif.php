<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['id_user'])) {
    die("Utilisateur non connecté");
}

$id_user = (int) $_SESSION['id_user'];

$sql_insert = "INSERT INTO tentatives (score, temps_ecoule, etat_tentative, status, id_user, date)
               VALUES (0, 0, 'en_cours', 'valide', ?, NOW())";

$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("i", $id_user);
$stmt->execute();

$id_t = $conn->insert_id;
$stmt->close();

$result = $conn->query("SELECT * FROM questions ORDER BY RAND() LIMIT 10");

$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

$_SESSION['id_t']      = $id_t;
$_SESSION['questions'] = $questions;
$_SESSION['index']     = 0;
$_SESSION['score']     = 0;

header("Location: quiz_modif.php");
exit;
?>