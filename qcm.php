<?php
session_start();

if (!isset($_SESSION['qcm'])) {
    header("Location: start.php");
    exit;
}

$index = $_SESSION['index'];
$question = $_SESSION['qcm'][$index];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Question <?= $index + 1 ?>/10</h2>

<form method="POST" action="traitement.php">
    <p><?= htmlspecialchars($question['question']) ?></p>

    <label><input type="radio" name="reponse" value="1" required> <?= $question['reponse1'] ?></label><br>
    <label><input type="radio" name="reponse" value="2"> <?= $question['reponse2'] ?></label><br>
    <label><input type="radio" name="reponse" value="3"> <?= $question['reponse3'] ?></label><br>
    <label><input type="radio" name="reponse" value="4"> <?= $question['reponse4'] ?></label><br>

    <button type="submit">Valider</button>
</form>
</body>
</html>