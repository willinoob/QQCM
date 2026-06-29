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

$questions = $_SESSION['questions'] ?? [];
$selected_categories_names = $_SESSION['selected_categories_names'] ?? [];

$corrections = [];
$nombre_bonnes = 0;

if ($etat === 'terminée') {

    $sql_rep = "SELECT id_q, reponse_user FROM reponses WHERE id_t = ?";
    $stmt_rep = $conn->prepare($sql_rep);
    $stmt_rep->bind_param("i", $id_t);
    $stmt_rep->execute();
    $resultat_rep = $stmt_rep->get_result();

    $reponses_par_question = [];
    while ($ligne = $resultat_rep->fetch_assoc()) {
        $reponses_par_question[(int) $ligne['id_q']] = (int) $ligne['reponse_user'];
    }
    $stmt_rep->close();

    foreach ($questions as $q) {
        $id_q = (int) $q['id_q'];
        $bonne = (int) $q['bonne_reponse'];
        $donnee = isset($reponses_par_question[$id_q]) ? $reponses_par_question[$id_q] : 0;

        $est_correct = ($donnee === $bonne);
        if ($est_correct) {
            $nombre_bonnes++;
        }

        $texte_donnee = ($donnee >= 1 && $donnee <= 4) ? $q["reponse$donnee"] : "Aucune réponse";
        $texte_bonne = $q["reponse$bonne"];

        $corrections[] = [
            'question' => $q['question'],
            'texte_donnee' => $texte_donnee,
            'texte_bonne' => $texte_bonne,
            'est_correct' => $est_correct,
        ];
    }
}

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
    <link rel="stylesheet" href="resultat.css">
</head>
<body>
    <main class="resultat-page">
        <section class="resultat-card">
            <h1>Résultat final</h1>

            <?php if (!empty($selected_categories_names)): ?>
                <p class="resultat-categories">Catégories choisies : <?= htmlspecialchars(implode(', ', $selected_categories_names)) ?></p>
            <?php endif; ?>

            <?php if ($etat === 'annulée'): ?>

            <p class="resultat-message">Votre tentative n'a pas été validée car certaines conditions n'ont pas été respectées.</p>
            <p class="resultat-score">Score : 0 / 20</p>

            <?php elseif ($etat === 'abandonnée'): ?>

                <p class="resultat-message">Vous avez abandonné le QCM.</p>
                <p class="resultat-score">Score : <?= $score ?> / 20</p>

            <?php elseif ($etat === 'terminée'): ?>

                <p class="resultat-score">Score : <?= $score ?> / 20</p>
                <p class="resultat-score secondaire">Bonnes réponses : <?= $nombre_bonnes ?> / <?= count($corrections) ?></p>

                <?php if ($score >= 16): ?>
                    <p class="niveau excellent">Niveau excellent.</p>
                <?php elseif ($score >= 10): ?>
                    <p class="niveau moyen">Niveau moyen.</p>
                <?php else: ?>
                    <p class="niveau faible">Niveau faible, il faut retravailler.</p>
                <?php endif; ?>

                <h2>Correction détaillée</h2>

                <div class="correction-list">
                    <?php foreach ($corrections as $c): ?>
                        <div class="correction-bloc <?= $c['est_correct'] ? 'correct' : 'incorrect' ?>">
                            <p><strong><?= htmlspecialchars($c['question']) ?></strong></p>

                            <?php if ($c['est_correct']): ?>
                                <p>Votre réponse : <span class="reponse-juste"><?= htmlspecialchars($c['texte_donnee']) ?></span></p>
                            <?php else: ?>
                                <p>Votre réponse : <span class="reponse-fausse"><?= htmlspecialchars($c['texte_donnee']) ?></span></p>
                                <p>Bonne réponse : <span class="bonne-reponse"><?= htmlspecialchars($c['texte_bonne']) ?></span></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            <div class="actions">
                <a href="start_quizz_modif.php">Refaire un QCM</a>
                <a href="home.php" class="secondary">Retour à l'accueil</a>
            </div>
        </section>
    </main>
</body>
</html>