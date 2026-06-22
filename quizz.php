<?php
session_start();

if (!isset($_SESSION['questions'])) {
    die("Aucun QCM actif");
}

$i = $_SESSION['index'];
$q = $_SESSION['questions'][$i];
?>

<h2><?= $q['question'] ?></h2>

<form method="POST" action="process.php">

<?php for ($j = 1; $j <= 4; $j++): ?>
    <label>
        <input type="radio" name="reponse" value="<?= $j ?>" required>
        <?= $q["reponse$j"] ?>
    </label><br>
<?php endfor; ?>

<br>
<button>Valider</button>

</form>

<p><?= $i + 1 ?> / 10</p>