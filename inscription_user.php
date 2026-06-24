<?php

session_start();
$erreurs = [];
$connect = mysqli_connect('localhost', 'root', '', 'qqcm');

if (!$connect) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

mysqli_set_charset($connect, 'utf8mb4');

if (isset($_POST['submit'])) {

    if (!empty($_POST['url'])) {
        exit();
    }

    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $mdp = $_POST['mdp'];
    $confirmation_mdp = $_POST['confirmation_mdp'];

    if (empty($prenom)) {
        $erreurs['prenom'] = "Le prénom est obligatoire.";
    }
    if (empty($nom)) {
        $erreurs['nom'] = "Le nom est obligatoire.";
    }
    if (empty($email)) {
        $erreurs['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = "L'email n'est pas valide.";
    }
    if (empty($mdp)) {
        $erreurs['mdp'] = "Le mot de passe est obligatoire.";
    } else {
        if (strlen($mdp) < 10) {
            $erreurs['mdp'] = "Le mot de passe doit contenir au moins 10 caractères.";
        } elseif (!preg_match('/[A-Z]/', $mdp)) {
            $erreurs['mdp'] = "Le mot de passe doit contenir au moins une majuscule.";
        } elseif (!preg_match('/[a-z]/', $mdp)) {
            $erreurs['mdp'] = "Le mot de passe doit contenir au moins une minuscule.";
        } elseif (!preg_match('/[0-9]/', $mdp)) {
            $erreurs['mdp'] = "Le mot de passe doit contenir au moins un chiffre.";
        }
    }
    if ($mdp !== $confirmation_mdp) {
        $erreurs['confirmation_mdp'] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($erreurs)) {
        $requete_email = mysqli_prepare($connect, "SELECT id_user FROM utilisateurs WHERE email = ?");
        mysqli_stmt_bind_param($requete_email, "s", $email);
        mysqli_stmt_execute($requete_email);
        $resultat = mysqli_stmt_get_result($requete_email);
        mysqli_stmt_close($requete_email);

        if (mysqli_num_rows($resultat) > 0) {
            $erreurs['email'] = "Cet email est déjà utilisé.";
        }
    }

    if (empty($erreurs)) {
        $mdp_hache = password_hash($mdp, PASSWORD_DEFAULT);

        // Valeurs fixées par le serveur, jamais par le formulaire :
        // un utilisateur qui s'inscrit lui-même est toujours "user" et "actif" par défaut.
        $role_par_defaut = 'user';
        $status_par_defaut = 'actif';

        $requete_insert = mysqli_prepare($connect,
            "INSERT INTO utilisateurs(nom, prenom, email, mot_de_passe, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($requete_insert, "ssssss", $nom, $prenom, $email, $mdp_hache, $role_par_defaut, $status_par_defaut);
        mysqli_stmt_execute($requete_insert);
        mysqli_stmt_close($requete_insert);

        header('Location: connexion.php');
        exit();
    }
}
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'inscription</title>
    <link rel="stylesheet" href="inscription_css.css">
</head>

<body class="page-inscription">

    <main class="auth-main">

        <div class="auth-box">
            <h1 class="auth-title">Inscrivez-vous</h1>

            <form action="" method="post" class="auth-form">

                <div class="form-group">
                    <label for="prenom">Prénom :</label><br>
                    <input type="text" name="prenom" id="prenom" placeholder="Ex : Marc"
                        value="<?php echo htmlspecialchars($prenom ?? ''); ?>" required><br>
                    <?php if (isset($erreurs['prenom'])): ?>
                        <p class="erreur"><?php echo htmlspecialchars($erreurs['prenom']); ?></p>
                    <?php endif; ?>
                </div><br>

                <div class="form-group">
                    <label for="nom">Nom :</label><br>
                    <input type="text" name="nom" id="nom" placeholder="Ex : Dubois"
                        value="<?php echo htmlspecialchars($nom ?? ''); ?>" required><br>
                    <?php if (isset($erreurs['nom'])): ?>
                        <p class="erreur"><?php echo htmlspecialchars($erreurs['nom']); ?></p>
                    <?php endif; ?>
                </div><br>

                <div class="form-group">
                    <label for="email">Adresse Email :</label><br>
                    <input type="email" name="email" id="email" placeholder="Ex : marcdubois@example.com"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>" required><br>
                    <?php if (isset($erreurs['email'])): ?>
                        <p class="erreur"><?php echo htmlspecialchars($erreurs['email']); ?></p>
                    <?php endif; ?>
                </div><br>

                <div class="form-group">
                    <label for="mdp">Mot de passe :</label><br>
                    <input type="password" name="mdp" id="mdp" required><br>
                    <?php if (isset($erreurs['mdp'])): ?>
                        <p class="erreur"><?php echo htmlspecialchars($erreurs['mdp']); ?></p>
                    <?php endif; ?>
                </div><br>

                <div class="form-group">
                    <label for="confirmation_mdp">Confirmer le mot de passe :</label><br>
                    <input type="password" name="confirmation_mdp" id="confirmation_mdp" required><br>
                    <?php if (isset($erreurs['confirmation_mdp'])): ?>
                        <p class="erreur"><?php echo htmlspecialchars($erreurs['confirmation_mdp']); ?></p>
                    <?php endif; ?>
                </div><br>

                <div class="form-rules">
                    <p>Votre mot de passe doit respecter les règles suivantes :</p>
                    <ul>
                        <li>Avoir au moins 10 caractères</li>
                        <li>Contenir au moins une majuscule</li>
                        <li>Contenir au moins une minuscule</li>
                        <li>Contenir au moins un chiffre</li>
                    </ul>
                </div>

                <div class="honeypot">
                    <input type="text" name="url" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-submit">
                    <button type="submit" name="submit" class="btn-submit">Je m'inscris</button>
                </div>

                <p class="lien">Déjà un compte ? <a href="connexion.php">Se connecter</a></p>

            </form>
        </div>

    </main>

</body>

</html>