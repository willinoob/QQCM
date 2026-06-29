<?php
require 'db_modif.php';

$utilisateurs = [];
$sql = 'SELECT id_user, nom, prenom, email, role, status FROM utilisateurs ORDER BY nom, prenom';
$resUsers = mysqli_query($conn, $sql);

if ($resUsers) {
    while ($ligne = mysqli_fetch_assoc($resUsers)) {
        $utilisateurs[] = $ligne;
    }
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_blocked($status)
{
    return strtolower((string) $status) === 'bloque';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Utilisateurs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="site-main">
    <div class="page-actions">
        <a href="dashboard_admin.php" class="btn-retour">Retour au panneau de controle</a>
    </div>

    <h1>Gestion des utilisateurs</h1>

    <?php if (isset($_GET['erreur'])): ?>
        <p class="erreur"><?= e($_GET['erreur']) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['succes'])): ?>
        <div class="success-msg"><?= e($_GET['succes']) ?></div>
    <?php endif; ?>

    <section class="admin-section">
        <h2>Liste des comptes</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prenom</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($utilisateurs) === 0): ?>
                        <tr>
                            <td colspan="6" class="empty-cell">Aucun utilisateur inscrit pour le moment.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($utilisateurs as $u): ?>
                        <?php
                        $idUser = (int) $u['id_user'];
                        $blocked = is_blocked($u['status']);
                        $isCurrentUser = $idUser === (int) ($_SESSION['id_user'] ?? 0);
                        ?>
                        <tr>
                            <td><?= e($u['nom']) ?></td>
                            <td><?= e($u['prenom']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="badge-role"><?= e($u['role']) ?></span></td>
                            <td>
                                <?php if ($blocked): ?>
                                    <span class="status-blocked">Compte bloque</span>
                                <?php else: ?>
                                    <span class="status-active">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <?php if ($isCurrentUser): ?>
                                    <span class="muted">Votre compte</span>
                                <?php else: ?>
                                    <form method="post" action="admin-users_action.php">
                                        <input type="hidden" name="id_user" value="<?= $idUser ?>">
                                        <?php if ($blocked): ?>
                                            <button type="submit" name="action" value="debloquer" class="btn-action-view">Debloquer</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="bloquer" class="btn-action-ban">Bloquer</button>
                                        <?php endif; ?>
                                    </form>

                                    <form method="post" action="admin-users_action.php">
                                        <input type="hidden" name="id_user" value="<?= $idUser ?>">
                                        <button type="submit" name="action" value="supprimer" class="btn-action-ban" onclick="return confirm('Supprimer ce compte utilisateur ?');">Supprimer</button>
                                    </form>
                                <?php endif; ?>
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
