<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test anti-triche</title>
</head>
<body>

    <h1>Page de test du QCM</h1>

    <button onclick="lancerTest()">Commencer le QCM (test)</button>

    <p>Temps restant : <span id="timer-qcm">--:--</span></p>

    <form id="formulaire-qcm" action="#" method="post" onsubmit="return false;">
        <p>Question 1 : 2 + 2 = ?</p>
        <input type="text" name="reponse1">
        <button type="submit">Soumettre (test)</button>
    </form>

    <p>Compteur d'avertissements actuel : <span id="debug-compteur">0</span></p>

    <!-- Overlay d'avertissement : caché par défaut, affiché par le JS -->
    <div id="overlay-avertissement" style="display:none;">
        <p id="message-avertissement"></p>
        <button onclick="reprendreApresAvertissement()">Reprendre le QCM</button>
    </div>

    <script src="anti-triche.js"></script>
    <script>
        function lancerTest() {
            // id_t fictif (1) et durée courte pour tester vite : 1 minute
            demarrerQcm(1, 1);

            // Ajout temporaire JUSTE pour le test, pour voir le compteur en direct
            setInterval(function() {
                document.getElementById('debug-compteur').textContent = antiTriche.nombreAvertissements;
            }, 300);
        }
    </script>

</body>
</html>