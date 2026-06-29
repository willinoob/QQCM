<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['questions']) || !isset($_SESSION['id_t']) || !isset($_SESSION['id_user'])) {
    die("Aucun QCM actif");
}

$id_t = (int) $_SESSION['id_t'];
$id_user = (int) $_SESSION['id_user'];
$questions = $_SESSION['questions'];


$sql_temps = "SELECT TIMESTAMPDIFF(SECOND, date, NOW()) AS temps_ecoule
              FROM tentatives WHERE id_t = ? AND id_user = ?";
$stmt_temps = $conn->prepare($sql_temps);
$stmt_temps->bind_param("ii", $id_t, $id_user);
$stmt_temps->execute();
$resultat_temps = $stmt_temps->get_result();
$ligne_temps = $resultat_temps->fetch_assoc();
$stmt_temps->close();

$duree_totale_secondes = 10 * 60;
$temps_ecoule = (int) $ligne_temps['temps_ecoule'];
$temps_restant_secondes = $duree_totale_secondes - $temps_ecoule;

if ($temps_restant_secondes < 0) {
    $temps_restant_secondes = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QCM en cours</title>
    <link rel="stylesheet" href="quizz.css">
</head>
<body>

    <div id="ecran-demarrage">
        <h1>Prêt à commencer ?</h1>
        <p>Le QCM se lancera en plein écran. Vous aurez 10 minutes.</p>
        <button onclick="demarrerQcm(<?= $id_t ?>, <?= $temps_restant_secondes ?>)">Démarrer le QCM</button>
    </div>

    <div id="ecran-qcm" style="display:none;">

        <p>Temps restant : <span id="timer-qcm">--:--</span></p>

        <form id="formulaire-qcm" method="POST" action="process_modif.php">

            <?php foreach ($questions as $q): ?>
                <?php $id_q = (int) $q['id_q']; ?>
                <div class="question-bloc">
                    <h3><?= htmlspecialchars($q['question']) ?></h3>
                    <?php for ($j = 1; $j <= 4; $j++): ?>
                        <label>
                            <input type="radio" name="reponse[<?= $id_q ?>]" value="<?= $j ?>">
                            <?= htmlspecialchars($q["reponse$j"]) ?>
                        </label><br>
                    <?php endfor; ?>
                </div>
                <hr>
            <?php endforeach; ?>

            <button type="button" onclick="terminerQcm()">Terminer le QCM</button>

        </form>

    </div>

    <div id="overlay-avertissement" style="display:none;">
        <p id="message-avertissement"></p>
        <button onclick="continuerQcm()">Continuer le QCM</button>
        <button onclick="arreterQcm()">Arrêter le QCM</button>
    </div>

    <script src="anti-triche.js"></script>
    <script src="summer-animations.js"></script>

</body>
</html>
