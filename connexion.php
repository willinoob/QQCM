<?php
session_start();

$erreur = null;
$email = '';

require 'db_modif.php';
mysqli_set_charset($connect, 'utf8mb4');

if (isset($_SESSION['id_user'])) {

    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: dashboard.php');
        exit();
    } else {
        header('Location: acceuil.php');
        exit();
    }
}


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
        $stmt = mysqli_prepare($connect, $sql);

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
                        header('Location: dashboard.php');
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

mysqli_close($connect);
?>