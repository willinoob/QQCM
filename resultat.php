<?php
session_start();
require "db.php";

$score = $_SESSION['score'];
$id_t = $_SESSION['id_t'];

$conn->query("
    UPDATE tentatives
    SET score = $score, etat_tentative = 'Terminé'
    WHERE id_t = $id_t
");

session_destroy();
?>

<h1>Résultat final</h1>

<p>Score : <?= $score ?> / 10</p>

<?php
if ($score >= 8) {
    echo "<p>Niveau excellent.</p>";
} elseif ($score >= 5) {
    echo "<p>Niveau moyen.</p>";
} else {
    echo "<p>Niveau faible, il faut retravailler.</p>";
}
?>