<?php 
session_start();

//connexion q la base de donnee
require "db_modif.php";
if (!$conn) {
    die("Erreur de connexion à la base : " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

//verification de la variable de session
if(!isset($_SESSION["id_user"]) || empty($_SESSION["id_user"])){
    header("Location: connexion.php");
    exit;
}

//lecture de l'onglet dans l'url
if (isset($_GET["onglet"])) {
    $onglet = $_GET["onglet"];
} else {
    $onglet = "historique";
}

//requete pour l'historique
$id_user = $_SESSION["id_user"];
if ($onglet == "historique") {
    $requete_preparée_hist = mysqli_prepare($conn, "SELECT id_t, date, score, etat_tentative, temps_ecoule
                                                    FROM tentatives
                                                    WHERE id_user = ? AND etat_tentative = 'terminée'
                                                    ORDER BY date DESC                                       ");
        mysqli_stmt_bind_param($requete_preparée_hist, "i", $id_user);
        mysqli_stmt_execute($requete_preparée_hist);
        $resultat_hist = mysqli_stmt_get_result($requete_preparée_hist);

 $historique = [];
    while ($ligne = mysqli_fetch_assoc($resultat_hist)) {
        $historique[] = $ligne;
    }
}

//requete pour la moyenne        
if ($onglet == "moyenne"){
    $requete_preparée_moy = mysqli_prepare($conn, "SELECT AVG(score) AS moyenne
                                                   FROM tentatives
                                                   WHERE id_user = ? AND etat_tentative = 'terminée'        ");
        mysqli_stmt_bind_param($requete_preparée_moy, "i", $id_user);
        mysqli_stmt_execute($requete_preparée_moy);
        $resultat_moy = mysqli_stmt_get_result($requete_preparée_moy);
        $moyenne = mysqli_fetch_assoc($resultat_moy);

}

//requete pour le classement
if ($onglet == "classement"){
    $requete_preparée_class = mysqli_prepare($conn, " SELECT u.nom, u.prenom, AVG(t.score) AS moyenne, COUNT(t.id_t) AS nb_tentatives
                                                    FROM tentatives t
                                                    JOIN utilisateurs u ON t.id_user = u.id_user
                                                    WHERE t.etat_tentative = 'terminée'
                                                    GROUP BY t.id_user
                                                    ORDER BY moyenne DESC                                   ");
        
        mysqli_stmt_execute($requete_preparée_class);
        $resultat_class = mysqli_stmt_get_result($requete_preparée_class);
        $classement = [];
  while ($ligne_class = mysqli_fetch_assoc($resultat_class)) {
        $classement[] = $ligne_class;
    }
}

//requete pour les stats
if ($onglet == "statistiques") {

//les détails par question
  $id_t = isset($_GET["id_t"]) ? $_GET["id_t"] : 0;
    $requete_preparée_det = mysqli_prepare( $conn," SELECT q.question,
                                                    COUNT(*) AS nb_reponses,
                                                    SUM(CASE WHEN r.reponse_user = q.bonne_reponse THEN 1 ELSE 0 END) AS nb_correctes
                                                    FROM reponses r
                                                    JOIN questions q ON r.id_q = q.id_q
                                                    WHERE r.id_t = ?
                                                    GROUP BY q.id_q
                                                    ORDER BY q.id_q ASC"
    );
        mysqli_stmt_bind_param($requete_preparée_det, "i", $id_t);
        mysqli_stmt_execute($requete_preparée_det);
        $resultat_det = mysqli_stmt_get_result($requete_preparée_det);

    $detail_tent = [];
    while ($ligne_det = mysqli_fetch_assoc($resultat_det)) {
        $detail_tent[] = $ligne_det;
    }

 // les questions les plus ratées
    $requete_preparée_rat = mysqli_prepare($conn," SELECT q.question,
                                                    COUNT(*) AS nb_reponses,
                                                    SUM(CASE WHEN r.reponse_user = q.bonne_reponse THEN 1 ELSE 0 END) AS nb_correctes,
                                                    (SUM(CASE WHEN r.reponse_user = q.bonne_reponse THEN 1 ELSE 0 END) / COUNT(*)) AS taux_reussite
                                                    FROM reponses r
                                                    JOIN questions q ON r.id_q = q.id_q
                                                    WHERE r.id_t = ?
                                                    GROUP BY q.id_q
                                                    ORDER BY taux_reussite ASC"
    );
    mysqli_stmt_bind_param($requete_preparée_rat, "i", $id_t);
    mysqli_stmt_execute($requete_preparée_rat);
    $resultat_rat = mysqli_stmt_get_result($requete_preparée_rat);

    $quest_rat = [];
    while ($ligne_rat = mysqli_fetch_assoc($resultat_rat)) {
        $quest_rat[] = $ligne_rat;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats QCM</title>

    <!-- Lien vers ton fichier CSS -->
    <link rel="stylesheet" href="style_historique.css">
</head>

<body>

    <h1>Résultats du QCM</h1>

    <!-- Navigation -->
    <nav>
        <a href="?onglet=historique">Historique</a>
        <a href="?onglet=moyenne">Moyenne</a>
        <a href="?onglet=classement">Classement</a>
        <a href="?onglet=statistiques&id_t=1">Statistiques</a>
    </nav>

    <hr>

    <!-- HISTORIQUE -->
    <?php if ($onglet == "historique") { ?>
        <h2>Historique des tentatives</h2>

        <table>
            <tr>
                <th>ID Tentative</th>
                <th>Date</th>
                <th>Score</th>
                <th>Temps écoulé</th>
            </tr>

            <?php foreach ($historique as $h) { ?>
                <tr>
                    <td><?= $h["id_t"] ?></td>
                    <td><?= $h["date"] ?></td>
                    <td><?= $h["score"] ?></td>
                    <td><?= $h["temps_ecoule"] ?> sec</td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>


    <!-- MOYENNE -->
    <?php if ($onglet == "moyenne") { ?>
        <h2>Moyenne générale</h2>

        <p><strong>Moyenne :</strong> <?= round($moyenne["moyenne"], 2) ?></p>
    <?php } ?>


    <!-- CLASSEMENT -->
    <?php if ($onglet == "classement") { ?>
        <h2>Classement des utilisateurs</h2>

        <table>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Moyenne</th>
                <th>Nb tentatives</th>
            </tr>

            <?php foreach ($classement as $c) { ?>
                <tr>
                    <td><?= $c["nom"] ?></td>
                    <td><?= $c["prenom"] ?></td>
                    <td><?= round($c["moyenne"], 2) ?></td>
                    <td><?= $c["nb_tentatives"] ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>


    <!-- STATISTIQUES -->
    <?php if ($onglet == "statistiques") { ?>
        <h2>Statistiques détaillées</h2>

        <h3>Détails par question</h3>
        <table>
            <tr>
                <th>Question</th>
                <th>Nb réponses</th>
                <th>Nb correctes</th>
            </tr>

            <?php foreach ($detail_tent as $d) { ?>
                <tr>
                    <td><?= $d["question"] ?></td>
                    <td><?= $d["nb_reponses"] ?></td>
                    <td><?= $d["nb_correctes"] ?></td>
                </tr>
            <?php } ?>
        </table>

        <h3>Questions les plus ratées</h3>
        <table>
            <tr>
                <th>Question</th>
                <th>Nb réponses</th>
                <th>Nb correctes</th>
                <th>Taux de réussite</th>
            </tr>

            <?php foreach ($quest_rat as $r) { ?>
                <tr>
                    <td><?= $r["question"] ?></td>
                    <td><?= $r["nb_reponses"] ?></td>
                    <td><?= $r["nb_correctes"] ?></td>
                    <td><?= round($r["taux_reussite"] * 100, 2) ?>%</td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>

    <p>
        <a href="acceuil.php">Accueil</a>
    </p>
</body>
</html>
