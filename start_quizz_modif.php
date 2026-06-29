<?php
session_start();
require "db_modif.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit;
}

$categories = [];
$result_categories = $conn->query("SELECT id_categorie, nom_categorie FROM categories ORDER BY nom_categorie");
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

$error_message = '';
$selected_categories = [];
$selected_categories_names = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_categories = array_values(array_unique(array_map('intval', $_POST['categories'] ?? [])));

    if (!empty($selected_categories)) {
        $category_ids = implode(',', $selected_categories);

        $sql_questions = "SELECT * FROM Questions WHERE id_categorie IN ($category_ids) ORDER BY RAND() LIMIT 10";
        $result_questions = $conn->query($sql_questions);

        $questions = [];
        if ($result_questions) {
            while ($row = $result_questions->fetch_assoc()) {
                $questions[] = $row;
            }
        }

        if (!empty($questions)) {
            $id_user = (int) $_SESSION['id_user'];
            $sql_insert = "INSERT INTO tentatives (score, temps_ecoule, etat_tentative, status, id_user, date)
                           VALUES (0, 0, 'en_cours', 'valide', ?, NOW())";
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $id_t = $conn->insert_id;
            $stmt->close();

            foreach ($categories as $category) {
                if (in_array((int) $category['id_categorie'], $selected_categories, true)) {
                    $selected_categories_names[] = $category['nom_categorie'];
                }
            }

            $_SESSION['id_t'] = $id_t;
            $_SESSION['questions'] = $questions;
            $_SESSION['index'] = 0;
            $_SESSION['score'] = 0;
            $_SESSION['selected_categories'] = $selected_categories;
            $_SESSION['selected_categories_names'] = $selected_categories_names;

            header("Location: quiz_modif.php");
            exit;
        }

        $error_message = 'Aucune question disponible pour les catégories sélectionnées.';
    } else {
        $error_message = 'Veuillez choisir au moins une catégorie.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir les catégories</title>
    <link rel="stylesheet" href="landing.css">
</head>
<body>
    <main class="start-page">
        <section class="start-card">
            <h1>Choisissez vos catégories</h1>
            <p>Sélectionnez une ou plusieurs catégories pour personnaliser votre QCM.</p>

            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>

            <form method="POST" action="start_quizz_modif.php">
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <label class="category-option">
                            <input type="checkbox" name="categories[]" value="<?= (int) $category['id_categorie'] ?>"
                                <?= in_array((int) $category['id_categorie'], $selected_categories, true) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($category['nom_categorie']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit">Lancer le QCM</button>
            </form>
        </section>
    </main>
</body>
</html>