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
</head>
<body>

    <h1>Bienvenue <?= htmlspecialchars($prenom) ?> !</h1>

    <p>Prêt à tester vos connaissances ?</p>

    <p>
        <a href="start_quizz_modif.php">Commencer un QCM</a>
    </p>

    <p>
        <a href="deconnexion.php">Se déconnecter</a>
    </p>

</body>
</html>