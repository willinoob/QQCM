# README — Interface Administrateur (Omar)

> Document pour comprendre ce qui existe déjà dans le projet et réaliser
> l'interface d'administration sans casser les autres parties.

## Objectif de ta partie

D'après le cahier des charges (point 9), l'administrateur doit pouvoir :

**Gestion des utilisateurs :**
- voir la liste des utilisateurs,
- supprimer un utilisateur,
- bloquer un utilisateur.

**Gestion des questions :**
- ajouter une question,
- modifier une question,
- supprimer une question.

Ta partie a une particularité : elle **touche aux données des autres parties** (les utilisateurs de l'authentification, les questions du QCM). Ce README explique ce qui existe déjà pour que tu puisses t'y brancher proprement, sans casser le travail des autres.

---

## La bonne nouvelle : presque tout est déjà en place

Tu n'as quasiment rien de nouveau à inventer côté base de données. Les tables existent déjà avec les champs qu'il te faut.

### Table `utilisateurs`

| Colonne | Type | Contenu | Utilité pour toi |
|---|---|---|---|
| `id_user` | INT | Identifiant | Pour cibler un utilisateur précis |
| `nom` | VARCHAR | Nom | À afficher dans la liste |
| `prenom` | VARCHAR | Prénom | À afficher dans la liste |
| `email` | VARCHAR | Email | À afficher dans la liste |
| `role` | VARCHAR | `user` ou `admin` | Pour distinguer les admins des utilisateurs normaux |
| `status` | VARCHAR | `actif` ou autre | **C'est ce champ qui sert à bloquer un utilisateur** |

### Table `questions`

| Colonne | Type | Contenu |
|---|---|---|
| `id_q` | INT | Identifiant de la question |
| `question` | TEXT | Le texte de la question |
| `reponse1` | VARCHAR | Réponse 1 |
| `reponse2` | VARCHAR | Réponse 2 |
| `reponse3` | VARCHAR | Réponse 3 |
| `reponse4` | VARCHAR | Réponse 4 |
| `bonne_reponse` | INT | Le numéro de la bonne réponse (1, 2, 3 ou 4) |
| `id_categorie` | INT | La catégorie de la question |

---

## Point de contact important : "bloquer un utilisateur"

C'est le point où ta partie se connecte directement à la mienne (authentification).

**Le mécanisme de blocage est DÉJÀ en place.** Le fichier `connexion.php` vérifie, à chaque connexion, que l'utilisateur a `status = 'actif'`. Si ce n'est pas le cas, la connexion est refusée avec le message "Votre compte a été bloqué. Contactez un administrateur."

**Donc pour bloquer un utilisateur, tu n'as rien de spécial à coder côté sécurité.** Il te suffit de changer son `status` en base (par exemple le passer à `'bloque'`) :

```sql
UPDATE utilisateurs SET status = 'bloque' WHERE id_user = ?;
```

Et automatiquement, cet utilisateur ne pourra plus se connecter. Pour le débloquer, tu remets `status = 'actif'`.

> Convention à respecter : la valeur "actif" doit rester exactement `'actif'` (c'est ce que `connexion.php` teste). Pour bloquer, tu peux utiliser n'importe quelle autre valeur (`'bloque'`, `'inactif'`...), du moment que ce n'est pas `'actif'`.

---

## Le seul vrai nouveau réflexe : protéger les pages admin

C'est la seule chose vraiment nouvelle par rapport aux autres parties. **Toutes tes pages admin doivent vérifier que l'utilisateur connecté est bien un admin**, sinon n'importe qui pourrait y accéder.

À mettre tout en haut de chaque page admin, après le `session_start()` :

```php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: acceuil.php");
    exit;
}
```

Bonne nouvelle : le rôle est **déjà stocké en session** à la connexion (dans `connexion.php`, on enregistre `$_SESSION['role']`). Tu n'as donc qu'à le lire.

> Pour tester ta partie, il te faudra au moins un compte avec `role = 'admin'`. Comme l'inscription crée toujours des comptes `user`, tu devras passer un compte en admin manuellement via phpMyAdmin : `UPDATE utilisateurs SET role = 'admin' WHERE email = 'ton@email.com';`

---

## Requêtes types pour démarrer

### Gestion des utilisateurs

**Lister tous les utilisateurs :**
```sql
SELECT id_user, nom, prenom, email, role, status FROM utilisateurs ORDER BY nom;
```

**Bloquer un utilisateur :**
```sql
UPDATE utilisateurs SET status = 'bloque' WHERE id_user = ?;
```

**Débloquer un utilisateur :**
```sql
UPDATE utilisateurs SET status = 'actif' WHERE id_user = ?;
```

**Supprimer un utilisateur :**
```sql
DELETE FROM utilisateurs WHERE id_user = ?;
```

> Attention sur la suppression : si un utilisateur a déjà des tentatives en base, supprimer son compte peut laisser des tentatives "orphelines" (qui pointent vers un utilisateur qui n'existe plus). À discuter avec l'équipe : soit on supprime aussi ses tentatives, soit on préfère simplement le bloquer plutôt que le supprimer. Le blocage est souvent préférable à la suppression pour cette raison.

### Gestion des questions

**Lister les questions :**
```sql
SELECT id_q, question, bonne_reponse FROM questions ORDER BY id_q;
```

**Ajouter une question :**
```sql
INSERT INTO questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie)
VALUES (?, ?, ?, ?, ?, ?, ?);
```

**Modifier une question :**
```sql
UPDATE questions
SET question = ?, reponse1 = ?, reponse2 = ?, reponse3 = ?, reponse4 = ?, bonne_reponse = ?
WHERE id_q = ?;
```

**Supprimer une question :**
```sql
DELETE FROM questions WHERE id_q = ?;
```

---

## Règles de sécurité à respecter (comme le reste du projet)

Le projet suit des conventions de sécurité partout. Respecte-les pour rester cohérent :

- **Requêtes préparées** (`prepare` + `bind_param`) pour TOUTES les requêtes, surtout les `INSERT`, `UPDATE`, `DELETE`. Jamais de variable directement dans une requête (protection injection SQL).
- **`htmlspecialchars()`** sur toute donnée affichée (protection XSS), par exemple le texte des questions ou les noms des utilisateurs.
- **Vérification du rôle admin** en haut de chaque page (voir plus haut).
- **Confirmation avant suppression** : pour les actions destructrices (supprimer un utilisateur ou une question), prévois une confirmation, pour éviter les suppressions accidentelles.

Tu peux t'inspirer des fichiers existants (`inscription_user.php`, `connexion.php`) qui appliquent déjà tous ces patterns : tu y verras comment sont faites les requêtes préparées et les validations.

---

## Connexion à la base

Réutilise le fichier `db_modif.php` déjà présent dans le projet :

```php
require "db_modif.php";
// $conn est disponible
```

Base : `qqcm` — encodage `utf8mb4`.

---

## Points de contact avec les autres parties (à connaître)

Ta partie touche aux données des autres. Voici les liens à garder en tête :

- **Les utilisateurs** que tu gères sont créés par l'authentification (Michel). Le champ `status` que tu utilises pour bloquer est lu par `connexion.php`.
- **Les questions** que tu gères sont tirées au hasard par le QCM (William). Si tu supprimes une question, elle ne sera simplement plus tirée : pas de problème pour les tentatives passées (elles ont déjà enregistré leurs réponses).
- **Les tentatives et réponses** (tables `tentatives` et `reponses`) sont remplies par le QCM et lues par l'historique (Mehdi). Évite d'y toucher depuis l'admin sauf besoin précis discuté avec l'équipe.
