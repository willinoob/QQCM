<?php
session_start();

echo "<h1>Résultat</h1>";
echo "<p>Score : " . $_SESSION['score'] . " / 10</p>";

// Nettoyage
session_destroy();