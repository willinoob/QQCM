<?php
require_once __DIR__ . '/../shared/admin_check.php';
require_once __DIR__ . '/../shared/db.php';

function redirect_users($type, $message)
{
    header('Location: admin-users.php?' . $type . '=' . urlencode($message));
    exit;
}

if (!isset($_POST['action'], $_POST['id_user'])) {
    header('Location: admin-users.php');
    exit;
}

$id_user = (int) $_POST['id_user'];
$action = $_POST['action'];
$current_user_id = (int) ($_SESSION['id_user'] ?? 0);

if ($id_user <= 0) {
    redirect_users('erreur', 'Utilisateur invalide.');
}

if ($id_user === $current_user_id) {
    redirect_users('erreur', 'Vous ne pouvez pas modifier votre propre compte depuis cet espace.');
}

if ($action === 'supprimer') {
    $stmt = mysqli_prepare($conn, 'DELETE FROM utilisateurs WHERE id_user = ?');
    if (!$stmt) {
        redirect_users('erreur', 'Erreur technique pendant la suppression.');
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        redirect_users('erreur', 'Impossible de supprimer cet utilisateur.');
    }

    redirect_users('succes', 'Utilisateur supprime avec succes.');
}

if ($action === 'bloquer' || $action === 'debloquer') {
    $nouveau_status = $action === 'bloquer' ? 'bloque' : 'actif';
    $stmt = mysqli_prepare($conn, 'UPDATE utilisateurs SET status = ? WHERE id_user = ?');

    if (!$stmt) {
        redirect_users('erreur', 'Erreur technique pendant le changement de statut.');
    }

    mysqli_stmt_bind_param($stmt, 'si', $nouveau_status, $id_user);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        redirect_users('erreur', 'Impossible de modifier le statut.');
    }

    $message = $nouveau_status === 'bloque' ? 'Utilisateur bloque avec succes.' : 'Utilisateur debloque avec succes.';
    redirect_users('succes', $message);
}

redirect_users('erreur', 'Action inconnue.');
