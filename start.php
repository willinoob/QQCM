<?php
session_start();
// Connexion MySQLi (adaptez les paramètres si besoin)
$id = mysqli_connect("localhost", "root", "root", "QQCM");
if (!$id) {
	die('DB connection failed: ' . mysqli_connect_error());
}

// Tirer 10 questions aléatoires
$res = mysqli_query($id, "SELECT * FROM Questions ORDER BY RAND() LIMIT 10");
if (!$res) {
	die('Query error: ' . mysqli_error($id));
}
$questions = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_close($id);

// Stocker en session
$_SESSION['qcm'] = $questions;
$_SESSION['index'] = 0;
$_SESSION['score'] = 0;

header("Location: qcm.php");
exit;