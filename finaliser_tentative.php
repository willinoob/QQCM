<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['succes' => false, 'message' => 'Utilisateur non connecté.']);
    exit();
}

$donnees = json_decode(file_get_contents('php://input'), true);

$id_t = isset($donnees['id_t']) ? (int) $donnees['id_t'] : 0;
$action = $donnees['action'] ?? '';

if ($id_t <= 0) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Identifiant de tentative invalide.']);
    exit();
}

if ($action === 'triche') {
    $nouvel_etat = 'annulée';
    $nouveau_status = 'invalide';
} elseif ($action === 'abandon') {
    $nouvel_etat = 'abandonnée';
    $nouveau_status = 'valide';
} else {
    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
    exit();
}

$connect = mysqli_connect('localhost', 'root', '', 'qqcm');
if (!$connect) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur de connexion à la base.']);
    exit();
}

mysqli_set_charset($connect, 'utf8mb4');

$id_user = (int) $_SESSION['id_user'];

$sql_verif = "SELECT etat_tentative, TIMESTAMPDIFF(SECOND, date, NOW()) AS temps_ecoule
              FROM tentatives WHERE id_t = ? AND id_user = ?";
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

if ($tentative['etat_tentative'] !== 'en_cours') {
    echo json_encode(['succes' => true, 'message' => 'Tentative déjà finalisée.']);
    mysqli_close($connect);
    exit();
}

$temps_ecoule = (int) $tentative['temps_ecoule'];

$sql_update = "UPDATE tentatives SET temps_ecoule = ?, etat_tentative = ?, status = ? WHERE id_t = ? AND id_user = ?";
$stmt_update = mysqli_prepare($connect, $sql_update);

if ($stmt_update === false) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur technique lors de la mise à jour.']);
    mysqli_close($connect);
    exit();
}

mysqli_stmt_bind_param($stmt_update, "issii", $temps_ecoule, $nouvel_etat, $nouveau_status, $id_t, $id_user);
mysqli_stmt_execute($stmt_update);
mysqli_stmt_close($stmt_update);

mysqli_close($connect);

echo json_encode(['succes' => true, 'message' => 'Tentative finalisée.']);
exit();
?>