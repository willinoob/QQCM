<?php
require 'db_modif.php';
$id_edit = null;
$question_texte = '';
$reponse1 = '';
$reponse2 = '';
$reponse3 = '';
$reponse4 = '';
$bonne_reponse = 1;
$id_categorie = null;

$categories = [];
$resCategories = mysqli_query($conn, 'SELECT id_categorie, nom_categorie FROM categories ORDER BY nom_categorie');
if ($resCategories) {
    while ($row = mysqli_fetch_assoc($resCategories)) {
        $categories[] = $row;
    }
}

if (isset($_GET['action'], $_GET['id_question']) && $_GET['action'] === 'modifier') {
    $id_edit = (int) $_GET['id_question'];

    $sql = 'SELECT id_q, question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie
            FROM Questions
            WHERE id_q = ?';
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_edit);
        mysqli_stmt_execute($stmt);
        $resultat = mysqli_stmt_get_result($stmt);

        if ($q = mysqli_fetch_assoc($resultat)) {
            $question_texte = $q['question'];
            $reponse1 = $q['reponse1'];
            $reponse2 = $q['reponse2'];
            $reponse3 = $q['reponse3'];
            $reponse4 = $q['reponse4'];
            $bonne_reponse = (int) $q['bonne_reponse'];
            $id_categorie = (int) $q['id_categorie'];
        } else {
            $id_edit = null;
        }

        mysqli_stmt_close($stmt);
    }
}

if ($id_categorie === null && count($categories) > 0) {
    $id_categorie = (int) $categories[0]['id_categorie'];
}

$questions = [];
$sqlQuestions = 'SELECT q.id_q, q.question, q.reponse1, q.reponse2, q.reponse3, q.reponse4,
                        q.bonne_reponse, c.nom_categorie
                 FROM Questions q
                 LEFT JOIN categories c ON c.id_categorie = q.id_categorie
                 ORDER BY q.id_q DESC';
$resQ = mysqli_query($conn, $sqlQuestions);
if ($resQ) {
    while ($row = mysqli_fetch_assoc($resQ)) {
        $questions[] = $row;
    }
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Questions</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="site-main">
    <div class="page-actions">
        <a href="dashboard_admin.php" class="btn-retour">Retour au panneau de controle</a>
    </div>

    <h1>Gestion des questions</h1>

    <?php if (isset($_GET['erreur'])): ?>
        <p class="erreur"><?= e($_GET['erreur']) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['succes'])): ?>
        <div class="success-msg"><?= e($_GET['succes']) ?></div>
    <?php endif; ?>

    <section class="admin-section">
        <h2><?= $id_edit ? 'Modifier la question #' . (int) $id_edit : 'Ajouter une question' ?></h2>

        <?php if (count($categories) === 0): ?>
            <p class="erreur">Ajoutez au moins une categorie en base avant de creer une question.</p>
        <?php else: ?>
            <form method="post" action="admin-questions_action.php" class="admin-form">
                <input type="hidden" name="id_question_edit" value="<?= e($id_edit ?? '') ?>">

                <label>
                    Enonce de la question
                    <textarea name="texte_question" required rows="3"><?= e($question_texte) ?></textarea>
                </label>

                <div class="grid-reponses">
                    <label>
                        Reponse 1
                        <input type="text" name="reponse_1" value="<?= e($reponse1) ?>" required>
                    </label>
                    <label>
                        Reponse 2
                        <input type="text" name="reponse_2" value="<?= e($reponse2) ?>" required>
                    </label>
                    <label>
                        Reponse 3
                        <input type="text" name="reponse_3" value="<?= e($reponse3) ?>" required>
                    </label>
                    <label>
                        Reponse 4
                        <input type="text" name="reponse_4" value="<?= e($reponse4) ?>" required>
                    </label>
                </div>

                <div class="form-row">
                    <label>
                        Bonne reponse
                        <select name="bonne_reponse" required>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?= $i ?>" <?= $bonne_reponse === $i ? 'selected' : '' ?>>
                                    Reponse <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </label>

                    <label>
                        Categorie
                        <select name="id_categorie" required>
                            <?php foreach ($categories as $categorie): ?>
                                <?php $categorieId = (int) $categorie['id_categorie']; ?>
                                <option value="<?= $categorieId ?>" <?= (int) $id_categorie === $categorieId ? 'selected' : '' ?>>
                                    <?= e($categorie['nom_categorie']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" name="enregistrer_question" class="btn-action-view">Enregistrer</button>
                    <?php if ($id_edit): ?>
                        <a href="admin-questions.php">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="admin-section">
        <h2>Pool de questions</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question et reponses</th>
                        <th>Categorie</th>
                        <th>Bonne reponse</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($questions) === 0): ?>
                        <tr>
                            <td colspan="5" class="empty-cell">Aucune question pour le moment.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?= (int) $q['id_q'] ?></td>
                            <td>
                                <strong><?= e($q['question']) ?></strong>
                                <ol class="answers-list">
                                    <li><?= e($q['reponse1']) ?></li>
                                    <li><?= e($q['reponse2']) ?></li>
                                    <li><?= e($q['reponse3']) ?></li>
                                    <li><?= e($q['reponse4']) ?></li>
                                </ol>
                            </td>
                            <td><?= e($q['nom_categorie'] ?? 'Sans categorie') ?></td>
                            <td>Reponse <?= (int) $q['bonne_reponse'] ?></td>
                            <td class="table-actions">
                                <a href="admin-questions.php?action=modifier&id_question=<?= (int) $q['id_q'] ?>" class="btn-action-view">Modifier</a>
                                <form method="post" action="admin-questions_action.php">
                                    <input type="hidden" name="id_question" value="<?= (int) $q['id_q'] ?>">
                                    <button type="submit" name="action_supprimer" class="btn-action-ban" onclick="return confirm('Supprimer cette question ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
