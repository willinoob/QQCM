<?php
$conn = mysqli_connect("localhost", "root", "root", "QQCM");
if (!$conn) {
    die("Erreur : " . mysqli_connect_error());
}

$nbUtilisateurs = 0;
$nbQuestions = 0;
$nbCategories = 0;

$res = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM utilisateurs');
if ($res && $row = mysqli_fetch_assoc($res)) {
    $nbUtilisateurs = (int) $row['total'];
}

$res = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM Questions');
if ($res && $row = mysqli_fetch_assoc($res)) {
    $nbQuestions = (int) $row['total'];
}

$res = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM categories');
if ($res && $row = mysqli_fetch_assoc($res)) {
    $nbCategories = (int) $row['total'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Projet QCM</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="site-main">
    <h1>Dashboard administrateur</h1>

    <section class="stats-grid">
        <article>
            <strong><?= $nbUtilisateurs ?></strong>
            <span>utilisateurs</span>
        </article>
        <article>
            <strong><?= $nbQuestions ?></strong>
            <span>questions</span>
        </article>
        <article>
            <strong><?= $nbCategories ?></strong>
            <span>categories</span>
        </article>
    </section>

    <nav class="admin-nav" aria-label="Navigation administrateur">
        <a href="admin-users.php" class="btn-action-view">Gerer les utilisateurs</a>
        <a href="admin-questions.php" class="btn-action-view">Gerer les questions</a>
        <a href="deconnexion.php" class="btn-action-ban">Deconnexion</a>
    </nav>
</main>
</body>
</html>
