<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit;
}

if (!isset($_SESSION['id_t'])) {
    header("Location: acceuil.php");
    exit;
}

$id_t = (int) $_SESSION['id_t'];
$id_user = (int) $_SESSION['id_user'];

$sql = "SELECT score, etat_tentative, status FROM tentatives WHERE id_t = ? AND id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_t, $id_user);
$stmt->execute();
$resultat = $stmt->get_result();
$tentative = $resultat->fetch_assoc();
$stmt->close();

if (!$tentative) {
    die("Tentative introuvable ou non autorisée.");
}

$score = (int) $tentative['score'];
$etat = $tentative['etat_tentative'];
$status = $tentative['status'];

unset($_SESSION['id_t']);
unset($_SESSION['questions']);
unset($_SESSION['index']);
unset($_SESSION['score']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat du QCM</title>
</head>

<body>

    <h1>Résultat final</h1>

    <?php if ($etat === 'annulée' || $status === 'invalide'): ?>

        <p>Votre tentative a été <strong>annulée</strong> suite à plusieurs infractions détectées.</p>
        <p>Aucun score n'est validé pour cette tentative.</p>

    <?php else: ?>

        <p>Score : <?= $score ?> / 20</p>

        <?php if ($score >= 16): ?>
            <p>Niveau excellent.</p>
        <?php elseif ($score >= 10): ?>
            <p>Niveau moyen.</p>
        <?php else: ?>
            <p>Niveau faible, il faut retravailler.</p>
        <?php endif; ?>

    <?php endif; ?>

    <p>
        <a href="start_quizz_modif.php">Refaire un QCM</a>
        |
        <a href="acceuil.php">Retour à l'accueil</a>
    </p>

</body>

</html>