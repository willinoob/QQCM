# README — Partie QCM (William)

> Document expliquant le fonctionnement et les modifications apportées aux fichiers du QCM.
> Objectif : permettre de comprendre et défendre cette partie, fichier par fichier.

## Vue d'ensemble

Cette partie gère tout le cycle de vie d'un QCM : création de la tentative, affichage des questions, traitement des réponses, calcul du score et affichage du résultat avec la correction.

Cette partie s'appuie sur cinq fichiers :

0. `db_modif.php` — connexion à la base de données (utilisé par tous les autres)
1. `start_quizz_modif.php` — prépare et démarre une tentative
2. `quiz_modif.php` — affiche le QCM (les 10 questions)
3. `process_modif.php` — reçoit les réponses, calcule le score, enregistre tout
4. `resultat_modif.php` — affiche le résultat et la correction détaillée

Une décision d'architecture importante traverse toute cette partie : **le QCM affiche les 10 questions sur une seule page (page unique)**, et non une question par page. La raison est expliquée plus bas, car elle conditionne le fonctionnement de tous les fichiers.

---

## Pourquoi le passage en "page unique" ?

### Le problème de départ

La version initiale affichait **une question par page** : on répondait, la page se rechargeait, la question suivante s'affichait, et ainsi de suite. C'est une approche classique et logique.

Mais cette approche était **incompatible avec le système anti-triche**. Voici pourquoi : le système anti-triche (plein écran, surveillance, compteur d'avertissements, timer) est géré en JavaScript. Or, **chaque rechargement de page réinitialise tout le JavaScript**. Concrètement, à chaque question :

- le plein écran se coupait,
- le compteur d'avertissements repartait de zéro,
- le timer se réinitialisait à 10:00.

La surveillance ne tenait donc jamais d'une question à l'autre. Un tricheur n'avait qu'à attendre le rechargement pour remettre les compteurs à zéro.

### La solution retenue

On affiche **les 10 questions d'un coup sur une seule page**. L'utilisateur répond à tout, puis valide une seule fois via un bouton "Terminer le QCM". Comme la page ne se recharge jamais entre les questions, le système anti-triche tourne **en continu** du début à la fin.

Cette solution a été validée avec l'équipe. La logique de calcul du score et de gestion des tentatives reste la même qu'avant ; seul le "moment" du traitement change : on traite les 10 réponses ensemble à la fin, au lieu d'une par une.

---

## Fichier 0 — `db_modif.php`

### Rôle

Ce fichier centralise la connexion à la base de données MySQL. Tous les autres fichiers de la partie QCM l'incluent avec `require "db_modif.php";` au lieu de réécrire la connexion à chaque fois.

### Ce qu'il fait

- Ouvre la connexion à la base `qqcm` (serveur `localhost`, utilisateur `root`).
- Vérifie que la connexion a réussi ; sinon, arrête le script avec un message d'erreur.
- Configure l'encodage des caractères en `utf8mb4`. C'est important pour que les accents s'affichent et s'enregistrent correctement (les questions en français, mais aussi les états comme `terminée`, `annulée`, `abandonnée`).

### Pourquoi un fichier séparé

Regrouper la connexion dans un seul fichier évite la duplication et facilite la maintenance : si les identifiants de la base changent, il n'y a qu'un seul endroit à modifier. La variable de connexion s'appelle `$conn` et est disponible dans tous les fichiers qui incluent `db_modif.php`.

---

## Fichier 1 — `start_quizz_modif.php`

### Rôle

Ce fichier démarre une nouvelle tentative de QCM. Il est appelé quand l'utilisateur clique sur "Commencer un QCM" depuis l'accueil.

### Ce qu'il fait, étape par étape

1. **Vérifie que l'utilisateur est connecté** (présence de `id_user` en session). Sinon, on bloque.
2. **Crée une nouvelle tentative en base** dans la table `tentatives`, avec :
   - `score = 0`, `temps_ecoule = 0` (valeurs de départ)
   - `etat_tentative = 'en_cours'`, `status = 'valide'`
   - `date = NOW()` : c'est **l'heure de début**, essentielle pour calculer le temps écoulé plus tard côté serveur.
3. **Récupère l'identifiant de la tentative** créée (`insert_id`) pour la suite.
4. **Tire 10 questions au hasard** dans la base (`ORDER BY RAND() LIMIT 10`).
5. **Stocke en session** : l'id de la tentative, les 10 questions, l'index et le score de départ.
6. **Redirige vers `quiz_modif.php`**.

### Modifications apportées et pourquoi

- **Requête d'insertion préparée** (`prepare` + `bind_param`) au lieu d'une requête directe : protection contre l'injection SQL.
- **`date = NOW()` ajouté à l'insertion** : sans cette heure de début enregistrée en base, il serait impossible de calculer le vrai temps écoulé côté serveur. C'est la fondation de toute la sécurité du temps.
- **États normalisés** : `'en_cours'` / `'valide'` dès la création, pour que la tentative soit dans un état clair dès le départ.

---

## Fichier 2 — `quiz_modif.php`

### Rôle

Affiche le QCM à l'utilisateur. C'est la page la plus modifiée, car c'est elle qui porte le passage en "page unique" et l'intégration de l'anti-triche.

### Ce qu'il fait, étape par étape

1. **Vérifie qu'un QCM est bien actif** (présence des questions, de l'id de tentative et de l'utilisateur en session). Sinon, message "Aucun QCM actif".
2. **Calcule le temps restant côté serveur** : on récupère le temps déjà écoulé depuis le début de la tentative (`TIMESTAMPDIFF`), et on le soustrait des 10 minutes. Ce calcul garantit que le timer affiché repart du bon endroit même après un rechargement, et qu'il ne peut pas être remis à 10:00 par triche.
3. **Affiche un écran de démarrage** avec un bouton "Démarrer le QCM".
4. **Affiche les 10 questions** (cachées tant que le QCM n'est pas démarré), chacune avec ses 4 réponses, le tout dans **un seul formulaire**.
5. **Inclut le système anti-triche** (`anti-triche.js`) et l'encart d'avertissement.

### Les points clés à comprendre

**Le bouton "Démarrer le QCM"** : c'est lui qui lance tout (plein écran + surveillance + affichage des questions). Il est indispensable pour une raison technique : le navigateur n'autorise le passage en plein écran **que** suite à un vrai clic utilisateur direct sur la page. Sans ce bouton, le plein écran ne se déclenchait pas (le clic d'origine, sur la page d'accueil, était "perdu" après les redirections).

**Un seul formulaire pour les 10 questions** : chaque réponse est nommée `reponse[ID_DE_LA_QUESTION]`. À l'envoi, le serveur reçoit donc un tableau associant chaque question à la réponse choisie. C'est ce qui permet d'envoyer les 10 réponses d'un coup et de les relier correctement à leurs questions.

**Un seul bouton "Terminer le QCM"** en bas, qui envoie tout vers `process_modif.php`.

### Modifications apportées et pourquoi

- **Passage de "une question par page" à "10 questions sur une page"** : pour permettre à l'anti-triche de fonctionner en continu (voir l'explication plus haut).
- **Ajout du calcul du temps restant côté serveur** : pour un timer fiable et non manipulable.
- **Ajout du bouton "Démarrer"** : pour déclencher le plein écran avec un vrai geste utilisateur.
- **Ajout de l'encart d'avertissement** (boutons "Continuer le QCM" / "Arrêter le QCM") : interface du système anti-triche.

---

## Fichier 3 — `process_modif.php`

### Rôle

Reçoit les réponses de l'utilisateur, calcule le score, enregistre tout en base, puis redirige vers le résultat. C'est le "cerveau" du traitement.

### Ce qu'il fait, étape par étape

1. **Vérifie qu'un QCM est actif** (session). Sinon, retour au démarrage.
2. **Récupère le temps écoulé et l'état de la tentative** côté serveur (`TIMESTAMPDIFF`).
3. **Empêche le double traitement** : si la tentative n'est plus `en_cours` (déjà terminée, abandonnée ou annulée), on ne recalcule pas, on redirige vers le résultat. Cela évite qu'un utilisateur resoumette le formulaire.
4. **Récupère les réponses envoyées** (le tableau `reponse[id_q]`).
5. **Calcule le score et enregistre chaque réponse** : pour chaque question, on compare la réponse donnée à la bonne réponse. Si c'est correct, on ajoute 2 points. Et **dans tous les cas**, on enregistre la réponse de l'utilisateur dans la table `reponses` (pour pouvoir afficher la correction ensuite).
6. **Met à jour la tentative** : score final, temps écoulé, `etat_tentative = 'terminée'`, `status = 'valide'`.
7. **Redirige vers `resultat_modif.php`**.

### Les points clés à comprendre

**Le score est calculé entièrement côté serveur.** La bonne réponse de chaque question vient de la session (donnée serveur), jamais du formulaire envoyé par le navigateur. L'utilisateur ne peut donc pas tricher sur la correction. Le barème : 10 questions × 2 points = 20 points maximum.

**L'enregistrement des réponses dans la table `reponses`** : chaque réponse est insérée avec `reponse_user`, `id_q` (la question) et `id_t` (la tentative). On réutilise une requête préparée dans la boucle pour l'efficacité. Cet enregistrement sert à reconstituer la correction détaillée sur la page de résultat, et permettra plus tard à la partie "historique" d'afficher le détail d'anciennes tentatives.

> Note : si une question est laissée sans réponse, on enregistre la valeur `0` (= "pas de réponse"), ce qui permet de l'afficher comme telle dans la correction.

### Modifications apportées et pourquoi

- **Traitement des 10 réponses d'un coup** au lieu d'une par une : conséquence directe du passage en page unique.
- **Calcul du score sur 20 côté serveur** : exigence du cahier des charges (le client ne doit jamais calculer le score lui-même).
- **Ajout de l'enregistrement dans la table `reponses`** : nécessaire pour la correction détaillée du point 6 du cahier des charges.
- **Protection contre le double traitement** : sécurité contre les resoumissions.

---

## Fichier 4 — `resultat_modif.php`

### Rôle

Affiche le résultat final à l'utilisateur : sa note, le nombre de bonnes réponses, et la correction détaillée question par question.

### Ce qu'il fait, étape par étape

1. **Vérifie que l'utilisateur est connecté et qu'une tentative est en session.** Sinon, redirige proprement (vers la connexion ou l'accueil).
2. **Récupère le résultat de la tentature** (score, état, statut) en base.
3. **Selon l'état de la tentative**, prépare l'affichage :
   - Si `terminée` : on relit les réponses de l'utilisateur depuis la table `reponses`, on les croise avec les questions, et on construit la correction.
   - Si `abandonnée` : pas de correction (les réponses n'ont pas été envoyées).
4. **Nettoie les variables de session du QCM** (`unset`), sans déconnecter l'utilisateur.
5. **Affiche le résultat**.

### Les trois cas d'affichage

| État | Affichage |
|---|---|
| `terminée` | Note sur 20, nombre de bonnes réponses, et correction colorée complète |
| `abandonnée` | Message d'abandon + score 0, sans correction |
| `annulée` (triche) | N'arrive jamais ici : l'utilisateur est redirigé vers l'accueil avant |

### La correction colorée

Pour chaque question, on affiche le **texte** de la réponse (et non son numéro), ce qui est plus clair pour l'utilisateur :

- Si sa réponse est correcte : elle s'affiche en **vert**.
- Si sa réponse est fausse : elle s'affiche en **rouge**, et la bonne réponse est affichée en dessous en vert.

Ce comportement répond directement au point 6 du cahier des charges (voir les questions ratées et leur bonne réponse).

### Pourquoi on n'utilise pas `session_destroy()` ici

On nettoie uniquement les variables liées au QCM (`id_t`, `questions`, `index`, `score`) avec `unset()`. L'utilisateur **reste connecté**. La destruction complète de la session est réservée à la déconnexion volontaire. Cela permet à l'utilisateur de refaire un QCM ou de revenir à l'accueil sans avoir à se reconnecter.

### Modifications apportées et pourquoi

- **Ajout de la correction détaillée colorée** : exigence du point 6 du cahier des charges (la version initiale n'affichait que le score).
- **Lecture des réponses depuis la table `reponses`** : pour reconstituer ce que l'utilisateur a répondu.
- **Affichage du texte des réponses** au lieu des numéros : plus intuitif.
- **Gestion des trois états** (terminée / abandonnée / annulée) : pour un affichage cohérent selon la situation.
- **Remplacement de `session_destroy()` par un `unset()` ciblé** : pour ne pas déconnecter l'utilisateur en fin de QCM.

---

## Résumé du flux complet

```
acceuil.php
    │  (clic "Commencer un QCM")
    ▼
start_quizz_modif.php   → crée la tentative (date NOW), tire 10 questions, redirige
    ▼
quiz_modif.php          → bouton "Démarrer" (plein écran + surveillance),
                          affiche les 10 questions, bouton "Terminer"
    │  (clic "Terminer le QCM")
    ▼
process_modif.php       → calcule le score sur 20 (serveur), enregistre les
                          réponses, met la tentative en "terminée"
    ▼
resultat_modif.php      → affiche note, bonnes réponses, correction colorée
```

---

## Lien avec la base de données

Trois tables sont impliquées :

- **`tentatives`** : une ligne par tentative (`score`, `date`, `temps_ecoule`, `etat_tentative`, `status`, `id_user`).
- **`questions`** : le pool de questions (`question`, `reponse1` à `reponse4`, `bonne_reponse`).
- **`reponses`** : une ligne par réponse donnée (`reponse_user`, `id_q`, `id_t`). Reliée à l'utilisateur indirectement, via la tentative (`id_t` → `tentatives.id_user`).
