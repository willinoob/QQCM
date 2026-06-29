<?php
require_once __DIR__ . '/../shared/admin_check.php';
require_once __DIR__ . '/../shared/db.php';

function redirect_questions($type, $message)
{
    header('Location: admin-questions.php?' . $type . '=' . urlencode($message));
    exit;
}

if (isset($_POST['enregistrer_question'])) {
    $question_texte = trim($_POST['texte_question'] ?? '');
    $r1 = trim($_POST['reponse_1'] ?? '');
    $r2 = trim($_POST['reponse_2'] ?? '');
    $r3 = trim($_POST['reponse_3'] ?? '');
    $r4 = trim($_POST['reponse_4'] ?? '');
    $bonne_reponse = (int) ($_POST['bonne_reponse'] ?? 0);
    $id_categorie = (int) ($_POST['id_categorie'] ?? 0);
    $id_edit = !empty($_POST['id_question_edit']) ? (int) $_POST['id_question_edit'] : null;

    if ($question_texte === '' || $r1 === '' || $r2 === '' || $r3 === '' || $r4 === '') {
        redirect_questions('erreur', 'Tous les champs sont obligatoires.');
    }

    if ($bonne_reponse < 1 || $bonne_reponse > 4) {
        redirect_questions('erreur', 'La bonne reponse doit etre comprise entre 1 et 4.');
    }

    $stmtCategorie = mysqli_prepare($conn, 'SELECT id_categorie FROM categories WHERE id_categorie = ?');
    if (!$stmtCategorie) {
        redirect_questions('erreur', 'Erreur technique pendant la verification de la categorie.');
    }

    mysqli_stmt_bind_param($stmtCategorie, 'i', $id_categorie);
    mysqli_stmt_execute($stmtCategorie);
    $categorieExiste = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCategorie));
    mysqli_stmt_close($stmtCategorie);

    if (!$categorieExiste) {
        redirect_questions('erreur', 'La categorie choisie est invalide.');
    }

    if ($id_edit) {
        $sql = 'UPDATE Questions
                SET question = ?, reponse1 = ?, reponse2 = ?, reponse3 = ?, reponse4 = ?, bonne_reponse = ?, id_categorie = ?
                WHERE id_q = ?';
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            redirect_questions('erreur', 'Erreur technique pendant la preparation de la modification.');
        }

        mysqli_stmt_bind_param($stmt, 'sssssiii', $question_texte, $r1, $r2, $r3, $r4, $bonne_reponse, $id_categorie, $id_edit);
        $message = 'Question modifiee avec succes.';
    } else {
        $sql = 'INSERT INTO Questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            redirect_questions('erreur', 'Erreur technique pendant la preparation de l ajout.');
        }

        mysqli_stmt_bind_param($stmt, 'sssssii', $question_texte, $r1, $r2, $r3, $r4, $bonne_reponse, $id_categorie);
        $message = 'Question ajoutee avec succes.';
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        redirect_questions('erreur', 'Impossible d enregistrer la question.');
    }

    mysqli_stmt_close($stmt);
    redirect_questions('succes', $message);
}

if (isset($_POST['action_supprimer'], $_POST['id_question'])) {
    $id_question = (int) $_POST['id_question'];

    if ($id_question <= 0) {
        redirect_questions('erreur', 'Question invalide.');
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM Questions WHERE id_q = ?');
    if (!$stmt) {
        redirect_questions('erreur', 'Erreur technique pendant la suppression.');
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_question);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        redirect_questions('erreur', 'Impossible de supprimer cette question.');
    }

    redirect_questions('succes', 'Question supprimee avec succes.');
}

header('Location: admin-questions.php');
exit;
