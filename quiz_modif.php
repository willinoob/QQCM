<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['questions']) || !isset($_SESSION['id_t']) || !isset($_SESSION['id_user'])) {
    die("Aucun QCM actif");
}

$i = (int) $_SESSION['index'];
$q = $_SESSION['questions'][$i];
$id_t = (int) $_SESSION['id_t'];
$id_user = (int) $_SESSION['id_user'];

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
</head>
<body>

    <p>Temps restant : <span id="timer-qcm">--:--</span></p>

    <h2><?= htmlspecialchars($q['question']) ?></h2>

    <form id="formulaire-qcm" method="POST" action="process_modif.php">

        <?php for ($j = 1; $j <= 4; $j++): ?>
            <label>
                <input type="radio" name="reponse" value="<?= $j ?>" required>
                <?= htmlspecialchars($q["reponse$j"]) ?>
            </label><br>
        <?php endfor; ?>

        <br>
        <button type="submit">Valider</button>

    </form>

    <p><?= $i + 1 ?> / 10</p>

    <div id="overlay-avertissement" style="display:none;">
        <p id="message-avertissement"></p>
        <button onclick="reprendreApresAvertissement()">Reprendre le QCM</button>
    </div>

    <script src="anti-triche.js"></script>
    <script>
        demarrerQcm(<?= $id_t ?>, <?= $temps_restant_secondes ?>);
    </script>

</body>
</html>