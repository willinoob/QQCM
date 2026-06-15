<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$utilisateur_connecte = isset($_SESSION['id_user']);
?>
<header class="site-header">
    <div class="site-header__inner">
        <a href="page_annonces.php" class="site-logo">
            <img src="assets/images/logo-leblogmoteur.png" alt="leblogmoteur">
        </a>
        <nav class="site-nav" aria-label="Navigation principale">
            <a href="page_annonces.php" class="site-nav__link">Annonces</a>
            <?php if ($utilisateur_connecte): ?>
                <a href="messages.php" class="site-nav__link">Messages</a>
                <a href="favoris.php" class="site-nav__link">Favoris</a>
                <a href="mon_compte.php" class="site-nav__link">Mon compte</a>
                <a href="formulaire_creation_annonces.php" class="site-nav__link">Déposer une annonce</a>
                <a href="deconnexion.php" class="site-nav__link">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php" class="site-nav__link">Connexion</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
