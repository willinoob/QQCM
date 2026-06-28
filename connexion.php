<?php
session_start();

$erreur = null;
$email = '';

$connect = mysqli_connect('localhost', 'root', 'root', 'bd_qqcm');
if (!$connect) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

mysqli_set_charset($connect, 'utf8mb4');

if (isset($_POST['connexion'])) {
    // --- Vérification du honeypot ---
    if (!empty($_POST['url'])) {
        exit();
    }

    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if ($email === '' || $mdp === '') {
        $erreur = 'Email ou mot de passe incorrect.';
    } else {
        // On récupère aussi role et status, nécessaires pour les vérifications après le mot de passe
        $sql = 'SELECT id_user, nom, prenom, mot_de_passe, role, status FROM utilisateurs WHERE email = ? LIMIT 1';
        $stmt = mysqli_prepare($connect, $sql);

        if ($stmt === false) {
            $erreur = 'Erreur technique. Veuillez réessayer.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $resultat = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($resultat);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($mdp, $user['mot_de_passe'])) {

                // Le mot de passe est correct : MAINTENANT on vérifie si le compte est actif.
                // On ne fait cette vérification qu'après le mot de passe, pour ne jamais révéler
                // l'état d'un compte à quelqu'un qui n'a même pas le bon mot de passe.
                if ($user['status'] !== 'actif') {
                    $erreur = 'Votre compte a été bloqué. Contactez un administrateur.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['id_user'] = (int) $user['id_user'];
                    $_SESSION['prenom']  = $user['prenom'];
                    $_SESSION['nom']     = $user['nom'];
                    $_SESSION['role']    = $user['role'];

                    header('Location: acceuil.php');
                    exit();
                }
            } else {
                // Message volontairement générique : on ne dit jamais
                // si c'est l'email OU le mot de passe qui est faux
                $erreur = 'Email ou mot de passe incorrect.';
            }
        }
    }
}

mysqli_close($connect);
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