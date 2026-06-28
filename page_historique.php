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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats QCM</title>

    <!-- Lien vers ton fichier CSS -->
    <link rel="stylesheet" href="page_historique.css">
</head>

<body>

    <h1>Résultats du QCM</h1>

    <!-- Navigation -->
    <nav>
        <a href="?onglet=historique">Historique</a>
        <a href="?onglet=moyenne">Moyenne</a>
        <a href="?onglet=classement">Classement</a>
    
    </nav>

    <hr>

    <!-- HISTORIQUE -->
    <?php if ($onglet == "historique") { ?>
      <h2>Historique des tentatives de <?= $_SESSION['prenom'] . " " . $_SESSION['nom'] ?></h2>


        <table>
            <tr>
                <th>Tentatives</th>
                <th>Date</th>
                <th>Score</th>
                <th>Temps écoulé</th>
            </tr>

                      
<?php 
    // Nombre total de tentatives
    $numero = count($historique);

    foreach ($historique as $h) { 
    ?>
        <tr>
            <td>Tentative n°<?= $numero ?></td>
            <td><?= $h["date"] ?></td>
            <td><?= $h["score"] ?></td>
            <td><?= $h["temps_ecoule"] ?> sec</td>
        </tr>
    <?php 
        $numero--; // On décrémente
    } 
    ?>


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


   

    <p>
        <a href="acceuil.php">Accueil</a>
    </p>
</body>
</html>