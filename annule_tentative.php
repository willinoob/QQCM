<?php
session_start();

header('Content-Type: application/json');

// 1. Vérification de la connexion utilisateur
if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['succes' => false, 'message' => 'Utilisateur non connecté.']);
    exit();
}

// 2. Récupération des données envoyées en JSON par le fetch()
$donnees = json_decode(file_get_contents('php://input'), true);

$id_t = isset($donnees['id_t']) ? (int) $donnees['id_t'] : 0;

if ($id_t <= 0) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Identifiant de tentative invalide.']);
    exit();
}

// 3. Connexion à la base
$connect = mysqli_connect('localhost', 'root', '', 'qqcm');
if (!$connect) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur de connexion à la base.']);
    exit();
}

mysqli_set_charset($connect, 'utf8mb4');

// 4. Vérification que CETTE tentative appartient bien à l'utilisateur connecté
$id_user = (int) $_SESSION['id_user'];

$sql_verif = "SELECT id_t, etat_tentative FROM tentatives WHERE id_t = ? AND id_user = ?";
$stmt_verif = mysqli_prepare($connect, $sql_verif);

if ($stmt_verif === false) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur technique lors de la vérification.']);
    mysqli_close($connect);
    exit();
}

mysqli_stmt_bind_param($stmt_verif, "ii", $id_t, $id_user);
mysqli_stmt_execute($stmt_verif);
$resultat_verif = mysqli_stmt_get_result($stmt_verif);
$tentative = mysqli_fetch_assoc($resultat_verif);
mysqli_stmt_close($stmt_verif);

if (!$tentative) {
    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Tentative introuvable ou non autorisée.']);
    mysqli_close($connect);
    exit();
}

// 5. On évite d'annuler une tentative déjà annulée
if ($tentative['etat_tentative'] === 'annulée') {
    echo json_encode(['succes' => true, 'message' => 'Tentative déjà annulée.']);
    mysqli_close($connect);
    exit();
}

// 6. La vraie mise à jour
$sql_update = "UPDATE tentatives SET etat_tentative = 'annulée', status = 'invalide' WHERE id_t = ? AND id_user = ?";
$stmt_update = mysqli_prepare($connect, $sql_update);

if ($stmt_update === false) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur technique lors de la mise à jour.']);
    mysqli_close($connect);
    exit();
}

mysqli_stmt_bind_param($stmt_update, "ii", $id_t, $id_user);
mysqli_stmt_execute($stmt_update);
mysqli_stmt_close($stmt_update);

mysqli_close($connect);

echo json_encode(['succes' => true, 'message' => 'Tentative annulée avec succès.']);
exit();