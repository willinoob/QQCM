<?php
session_start();
require "db.php";

$id_user = 1; // session utilisateur plus tard

// création tentative
$conn->query("
    INSERT INTO tentatives (score, temps_ecoule, etat_tentative, id_user)
    VALUES (0, 0, 'En cours', $id_user)
");

$id_t = $conn->insert_id;

// 10 questions aléatoires
$res = $conn->query("
    SELECT * 
    FROM Questions 
    ORDER BY RAND() 
    LIMIT 10
");

$questions = [];
while ($row = $res->fetch_assoc()) {
    $questions[] = $row;
}

$_SESSION['id_t'] = $id_t;
$_SESSION['questions'] = $questions;
$_SESSION['index'] = 0;
$_SESSION['score'] = 0;

header("Location: quiz.php");
exit;
?>