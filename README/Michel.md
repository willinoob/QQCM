# README — Module Authentification & Anti-triche (Michel)

## Objectif de ma partie

Mon module couvre deux responsabilités principales du projet :

1. **L'authentification** : permettre aux utilisateurs de s'inscrire, se connecter et se déconnecter de façon sécurisée.
2. **L'anti-triche** : surveiller le déroulement d'un QCM pour limiter les possibilités de triche, et garantir que le temps et la validité d'une tentative soient contrôlés côté serveur.

Le principe directeur de tout le module est le suivant : **on ne fait jamais confiance au client (navigateur).** Tout ce qui touche à la sécurité (mots de passe, temps écoulé, score, validité d'une tentative) est vérifié et calculé côté serveur (PHP/MySQL). Le JavaScript ne sert qu'à l'expérience utilisateur et à la dissuasion, jamais de source de vérité.

---

## Les fichiers de ma partie

### `db_modif.php` — Connexion à la base de données

Centralise la connexion MySQL utilisée par tous les fichiers. Définit la variable `$conn`, configure l'encodage en `utf8mb4` (pour gérer correctement les accents : "terminée", "annulée"...). Inclus via `require` dans les autres fichiers.

### `inscription_user.php` — Création de compte

Gère l'inscription d'un nouvel utilisateur.

**Logique de sécurité mise en place :**

- **Validation des champs** : prénom, nom, email obligatoires ; email au bon format (`filter_var`) ; mot de passe d'au moins 10 caractères avec majuscule, minuscule et chiffre (regex).
- **Email unique** : vérification en base qu'aucun compte n'existe déjà avec cet email.
- **Hash du mot de passe** : `password_hash()` avec `PASSWORD_DEFAULT` (bcrypt). Le mot de passe en clair n'est jamais stocké.
- **Honeypot anti-bot** : un champ caché (`url`) invisible pour un humain. Si un bot le remplit, le script s'arrête silencieusement.
- **Role et status par défaut imposés côté serveur** : tout nouvel inscrit est `role = 'user'` et `status = 'actif'`. Ces valeurs ne viennent jamais du formulaire (sécurité : un utilisateur ne peut pas s'auto-déclarer admin).
- **Protection injection SQL** : toutes les requêtes sont préparées (`mysqli_prepare` + `bind_param`).

### `connexion.php` — Connexion

Gère la connexion d'un utilisateur existant.

**Logique de sécurité :**

- **Requête préparée** pour récupérer l'utilisateur par email (anti-injection SQL).
- **`password_verify()`** : compare le mot de passe saisi au hash stocké, sans jamais déchiffrer.
- **Vérification du statut** : après validation du mot de passe seulement, on vérifie que `status = 'actif'`. Un compte bloqué ne peut pas se connecter. Cette vérification se fait **après** le mot de passe pour ne pas révéler l'état d'un compte à quelqu'un qui n'a pas les bons identifiants.
- **Message d'erreur générique** : "Email ou mot de passe incorrect" dans tous les cas d'échec, pour empêcher l'énumération des comptes existants.
- **`session_regenerate_id(true)`** : régénère l'identifiant de session après connexion, pour se protéger contre la fixation de session.
- **Honeypot** également présent.
- Après connexion réussie, redirection vers `acceuil.php`.

### `acceuil.php` — Page d'accueil (espace connecté)

Page d'arrivée après connexion. Accessible uniquement aux utilisateurs connectés (vérification de session, sinon redirection vers la connexion).

**Rôle particulier — nettoyage des tentatives fantômes :** à chaque passage sur l'accueil, on cherche les tentatives restées bloquées en `etat_tentative = 'en_cours'` (cas où l'utilisateur a fermé brutalement son navigateur en plein QCM) et on les passe en `'abandonnée'`. Cela évite d'avoir des tentatives "fantômes" jamais clôturées en base.

Contient le bouton "Commencer un QCM" et le lien de déconnexion.

### `deconnexion.php` — Déconnexion

Détruit la session (`session_destroy()`) et redirige vers la connexion. C'est le **seul** fichier du projet où `session_destroy()` est utilisé : ailleurs, on nettoie uniquement les variables liées au QCM avec `unset()`, sans déconnecter l'utilisateur.

### `anti-triche.js` — Surveillance côté client

Le cœur du système anti-triche. Activé uniquement pendant un QCM, via la fonction `demarrerQcm(id_t, dureeSecondes)` déclenchée par le bouton "Démarrer le QCM".

**Mécanismes de surveillance :**

- **Plein écran obligatoire** : demandé au démarrage. Le bouton "Démarrer" garantit un vrai clic utilisateur, nécessaire pour que le navigateur autorise le plein écran.
- **Détection de sortie du plein écran** (événement `fullscreenchange`).
- **Détection de changement d'onglet / minimisation** (`visibilitychange` et `blur`).
- **Système d'avertissements** : chaque infraction incrémente un compteur. À chaque avertissement, le QCM se met en pause (timer figé visuellement) et un encart propose "Continuer le QCM" ou "Arrêter le QCM". Après 3 avertissements, la tentative est automatiquement annulée pour triche.
- **Blocages** : clic droit, copier-coller, sélection de texte, raccourcis clavier sensibles (F12, Ctrl+Shift+I/C/J, Ctrl+U).
- **Filtre anti-doublon** : deux événements rapprochés (moins de 500 ms) ne comptent que pour une seule infraction (évite qu'un seul changement d'onglet ne compte double).
- **Timer** : décompte visuel. Quand il atteint zéro, le QCM est soumis automatiquement.

**Communication serveur** : en cas d'abandon volontaire ou d'annulation pour triche, le JS envoie une requête (`fetch`) à `finaliser_tentative.php` pour mettre à jour l'état en base, puis redirige (résultat pour l'abandon, accueil pour la triche).

**Limite assumée :** la surveillance côté client peut être contournée (fermeture du navigateur, JavaScript désactivé, second écran). C'est une limite intrinsèque à toute détection côté client. C'est pourquoi la vérité du temps et du score est toujours validée côté serveur. Sur un système à fort enjeu, on ajouterait une surveillance humaine (webcam, surveillant).

### `finaliser_tentative.php` — Finalisation d'une tentative interrompue

Point d'entrée unique appelé par le JS quand une tentative se termine de façon anticipée. Reçoit l'`id_t` et une `action` (`'triche'` ou `'abandon'`) et met à jour la base en conséquence :

| Action                      | `etat_tentative` | `status`   |
| --------------------------- | ---------------- | ---------- |
| `triche` (3 avertissements) | `annulée`        | `invalide` |
| `abandon` (clic "Arrêter")  | `abandonnée`     | `valide`   |

**Sécurité :** vérifie que l'utilisateur est connecté, que la tentative lui appartient bien (`WHERE id_t = ? AND id_user = ?`), et n'agit que si la tentative est encore `en_cours`. Le temps réel écoulé est calculé côté serveur (`TIMESTAMPDIFF`) et enregistré, indépendamment du timer JavaScript.

---

## La gestion du temps (point clé de sécurité)

Le temps est le meilleur exemple du principe "client propose, serveur impose" :

- **Côté client (JS)** : un timer décompte visuellement les 10 minutes. Pendant un avertissement, il est figé visuellement pour le confort.
- **Côté serveur (PHP/MySQL)** : le vrai temps écoulé est toujours recalculé avec `TIMESTAMPDIFF(SECOND, date, NOW())`, où `date` est l'heure de début enregistrée à la création de la tentative.

Conséquence : même si un utilisateur fige son timer ou trafique le JavaScript, le serveur connaît le vrai temps écoulé. Le timer affiché n'a aucune valeur de preuve.

---

## Les états d'une tentative

Une tentative passe par différents états enregistrés en base :

| `etat_tentative` | `status`   | Signification                                                            |
| ---------------- | ---------- | ------------------------------------------------------------------------ |
| `en_cours`       | `valide`   | QCM démarré, pas encore terminé                                          |
| `terminée`       | `valide`   | QCM complété normalement (score enregistré)                              |
| `abandonnée`     | `valide`   | L'utilisateur a arrêté volontairement (score 0, temps enregistré)        |
| `annulée`        | `invalide` | Tentative annulée pour triche (3 avertissements), aucun résultat affiché |

---

## Récapitulatif des protections de sécurité

| Risque                 | Protection                                            |
| ---------------------- | ----------------------------------------------------- |
| Injection SQL          | Requêtes préparées (`prepare` + `bind_param`) partout |
| XSS                    | `htmlspecialchars()` sur toute donnée affichée        |
| Vol de mots de passe   | `password_hash` / `password_verify` (bcrypt)          |
| Fixation de session    | `session_regenerate_id(true)` à la connexion          |
| Énumération de comptes | Message d'erreur générique                            |
| Bots / spam            | Honeypot                                              |
| Triche pendant le QCM  | Surveillance JS (plein écran, onglet, blocages)       |
| Triche sur le temps    | Recalcul serveur indépendant du JS                    |
| Élévation de privilège | `role`/`status` imposés côté serveur à l'inscription  |
