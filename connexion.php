<?php

session_start();

session_unset();
session_destroy();

session_start();

$erreur = null;
$email = '';

require 'db_modif.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {

    /* honeypot anti-bot */
    if (!empty($_POST['url'] ?? '')) {
        exit();
    }

    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if ($email === '' || $mdp === '') {
        $erreur = 'Email ou mot de passe incorrect.';
    } else {

        $sql = 'SELECT * FROM utilisateurs WHERE email = ? LIMIT 1';
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            $erreur = 'Erreur technique. Veuillez réessayer.';
        } else {

            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);

            $resultat = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($resultat);

            mysqli_stmt_close($stmt);

            if ($user && password_verify($mdp, $user['mot_de_passe'])) {

                if ($user['status'] !== 'actif') {
                    $erreur = 'Votre compte a été bloqué.';
                } else {

                    session_regenerate_id(true);

                    $_SESSION['id_user'] = (int)$user['id_user'];
                    $_SESSION['prenom']  = $user['prenom'];
                    $_SESSION['nom']     = $user['nom'];
                    $_SESSION['role']    = $user['role'];

                    if ($user['role'] === 'admin') {
                        header('Location: dashboard_admin.php');
                    } else {
                        header('Location: acceuil.php');
                    }
                    exit();
                }

            } else {
                $erreur = 'Email ou mot de passe incorrect.';
            }
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="connexion_css.css">
</head>

<body class="page-connexion">

    <div class="box">

        <h1 class="auth-title">Connectez-vous</h1>
        <?php if ($erreur !== null): ?>
            <p class="erreur"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>
        <form action="" method="post" class="auth-form">
            <div class="form-group">
                <label for="email">Email :</label><br>
                <input type="email" name="email" id="email" placeholder="Ex : marcdubois@example.com"
                    value="<?php echo htmlspecialchars($email); ?>" required>
            </div><br>
            <div class="form-group">
                <label for="mdp">Mot de passe :</label><br>
                <input type="password" name="mdp" id="mdp" placeholder="Mot de passe" required>
            </div><br>
            <div class="honeypot">
                <input type="text" name="url" tabindex="-1" autocomplete="off">
            </div>
            <div class="form-submit">
                <button type="submit" name="connexion" class="btn-submit">Je me connecte</button>
            </div>
        </form>
        <p class="lien">Pas de compte ? <a href="inscription_user.php">S'inscrire</a></p>

    </div>

</body>

</html>