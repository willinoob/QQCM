<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$prenom = $_SESSION['prenom'] ?? '';

$sql_nettoyage = "UPDATE tentatives
                  SET etat_tentative = 'abandonnée'
                  WHERE id_user = ? AND etat_tentative = 'en_cours'";
$stmt_nettoyage = $conn->prepare($sql_nettoyage);
$stmt_nettoyage->bind_param("i", $id_user);
$stmt_nettoyage->execute();
$stmt_nettoyage->close();

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <header class="hero-header">
        <div class="hero-content">
            <p class="hero-subtitle">Bienvenue</p>
            <h1><?= htmlspecialchars($prenom) ?> !</h1>
            <p class="hero-text">Prêt à tester vos connaissances et à progresser à chaque QCM ?</p>
            <nav class="hero-nav">
                <a href="start_quizz_modif.php">Commencer un QCM</a>
                <a href="page_historique.php">Voir l'historique</a>
                <a href="deconnexion.php" class="secondary">Se déconnecter</a>
            </nav>
        </div>
    </header>
</body>
</html>
