<?php
session_start();

if (isset($_SESSION['id_user'])) {
    header('Location: page_annonces.php');
    exit;
}

$conn = mysqli_connect('localhost', 'root', '', 'bd_blogmoteur');
$erreurs = [];

$valeurs = [
    'nom'    => '',
    'prenom' => '',
    'email'  => '',
];

if (!$conn) {
    $erreurs['global'] = 'Impossible de se connecter à la base de données.';
}

if ($conn && isset($_POST['inscrire'])) {

    $valeurs['nom']    = trim($_POST['nom'] ?? '');
    $valeurs['prenom'] = trim($_POST['prenom'] ?? '');
    $valeurs['email']  = trim($_POST['email'] ?? '');
    $mdp               = $_POST['mdp'] ?? '';
    $confirmation_mdp  = $_POST['confirmation_mdp'] ?? '';

    if ($valeurs['nom'] === '') {
        $erreurs['nom'] = 'Le nom est obligatoire.';
    }
    if ($valeurs['prenom'] === '') {
        $erreurs['prenom'] = 'Le prénom est obligatoire.';
    }
    if ($valeurs['email'] === '') {
        $erreurs['email'] = 'L\'email est obligatoire.';
    } elseif (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = 'L\'email n\'est pas valide.';
    }
    if ($mdp === '') {
        $erreurs['mdp'] = 'Le mot de passe est obligatoire.';
    } elseif (strlen($mdp) < 10) {
        $erreurs['mdp'] = 'Le mot de passe doit contenir au moins 10 caractères.';
    } elseif ($mdp !== $confirmation_mdp) {
        $erreurs['confirmation_mdp'] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($erreurs)) {
        $sql_check = 'SELECT id_user FROM users WHERE email = ? LIMIT 1';
        $stmt_check = mysqli_prepare($conn, $sql_check);

        if ($stmt_check === false) {
            $erreurs['global'] = 'Erreur technique. Veuillez réessayer.';
        } else {
            mysqli_stmt_bind_param($stmt_check, 's', $valeurs['email']);
            mysqli_stmt_execute($stmt_check);
            $existe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check));
            mysqli_stmt_close($stmt_check);

            if ($existe) {
                $erreurs['email'] = 'Cet email est déjà utilisé.';
            } else {
                $mdp_hache = password_hash($mdp, PASSWORD_DEFAULT);

                $sql_insert = 'INSERT INTO users (nom, prenom, email, password) VALUES (?, ?, ?, ?)';
                $stmt_insert = mysqli_prepare($conn, $sql_insert);

                if ($stmt_insert === false) {
                    $erreurs['global'] = 'Erreur technique. Veuillez réessayer.';
                } else {
                    mysqli_stmt_bind_param(
                        $stmt_insert,
                        'ssss',
                        $valeurs['nom'],
                        $valeurs['prenom'],
                        $valeurs['email'],
                        $mdp_hache
                    );

                    if (!mysqli_stmt_execute($stmt_insert)) {
                        $erreurs['global'] = 'Impossible de créer le compte.';
                    } else {
                        $_SESSION['id_user'] = (int) mysqli_insert_id($conn);
                        $_SESSION['prenom']  = $valeurs['prenom'];
                        $_SESSION['nom']     = $valeurs['nom'];
                        mysqli_stmt_close($stmt_insert);
                        mysqli_close($conn);
                        header('Location: page_annonces.php');
                        exit;
                    }

                    mysqli_stmt_close($stmt_insert);
                }
            }
        }
    }
}

if ($conn) {
    mysqli_close($conn);
}

function champ_val($valeurs, $nom) {
    return htmlspecialchars($valeurs[$nom] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Le Blog Moteur</title>
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="page-connexion">

    <div class="box">

        <h1>leblog<span class="brand-accent">moteur</span></h1>
        <hr>

        <form action="" method="post">

            <input type="text" name="nom" placeholder="Nom" required
                   value="<?= champ_val($valeurs, 'nom') ?>"><br><br>
            <?php if (isset($erreurs['nom'])): ?>
                <p class="erreur"><?= htmlspecialchars($erreurs['nom']) ?></p>
            <?php endif; ?>

            <input type="text" name="prenom" placeholder="Prénom" required
                   value="<?= champ_val($valeurs, 'prenom') ?>"><br><br>
            <?php if (isset($erreurs['prenom'])): ?>
                <p class="erreur"><?= htmlspecialchars($erreurs['prenom']) ?></p>
            <?php endif; ?>

            <input type="email" name="email" placeholder="Email" required
                   value="<?= champ_val($valeurs, 'email') ?>"><br><br>
            <?php if (isset($erreurs['email'])): ?>
                <p class="erreur"><?= htmlspecialchars($erreurs['email']) ?></p>
            <?php endif; ?>

            <input type="password" name="mdp" placeholder="Mot de passe" required><br><br>
            <?php if (isset($erreurs['mdp'])): ?>
                <p class="erreur"><?= htmlspecialchars($erreurs['mdp']) ?></p>
            <?php endif; ?>

            <input type="password" name="confirmation_mdp" placeholder="Confirmer le mot de passe" required><br><br>
            <?php if (isset($erreurs['confirmation_mdp'])): ?>
                <p class="erreur"><?= htmlspecialchars($erreurs['confirmation_mdp']) ?></p>
            <?php endif; ?>

            <?php if (isset($erreurs['global'])): ?>
                <p class="erreur"><?= htmlspecialchars($erreurs['global']) ?></p>
            <?php endif; ?>

            <input type="submit" value="S'inscrire" name="inscrire">

        </form>

        <p class="lien">Déjà un compte ? <a href="connexion.php">Se connecter</a></p>

    </div>

</body>
</html>
