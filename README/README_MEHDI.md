# README — Partie Historique & Moyenne (Mehdi)

> Document pour comprendre les données disponibles et réaliser la partie
> historique, moyenne, classement et statistiques.

## Objectif de ta partie

D'après le cahier des charges (point 7), tu dois permettre à l'utilisateur de consulter :

- ses tentatives précédentes (liste avec date et score),
- sa moyenne générale,
- le classement des utilisateurs,
- l'export des résultats,
- des statistiques détaillées.

**Bonne nouvelle : toutes les données dont tu as besoin existent déjà en base.** Le système de QCM remplit automatiquement les tables `tentatives` et `reponses` à chaque passage. Tu n'as donc rien à insérer manuellement : ta partie consiste à **lire** ces données et à les afficher. Pour avoir des données de test, il te suffit de te connecter et de passer quelques QCM (en variant : certains terminés, un abandonné, un annulé pour triche).

---

## Les tables que tu vas utiliser

### Table `tentatives` (ta source principale)

Une ligne = une tentative de QCM par un utilisateur.

| Colonne | Type | Contenu |
|---|---|---|
| `id_t` | INT | Identifiant unique de la tentative |
| `score` | INT | Note obtenue, sur 20 |
| `date` | DATETIME | Date et heure de début de la tentative |
| `temps_ecoule` | INT | Durée de la tentative, en **secondes** |
| `etat_tentative` | VARCHAR | État de la tentative (voir tableau ci-dessous) |
| `status` | VARCHAR | `valide` ou `invalide` |
| `id_user` | INT | L'utilisateur à qui appartient la tentative |

### Table `reponses` (pour les statistiques détaillées)

Une ligne = une réponse donnée à une question, pendant une tentative.

| Colonne | Type | Contenu |
|---|---|---|
| `id_rep` | INT | Identifiant unique de la réponse |
| `reponse_user` | INT | Numéro de la réponse choisie (1 à 4), ou `0` si la question n'a pas été répondue |
| `id_q` | INT | La question concernée |
| `id_t` | INT | La tentative concernée |

### Table `utilisateurs` (pour le classement)

| Colonne | Type | Contenu |
|---|---|---|
| `id_user` | INT | Identifiant de l'utilisateur |
| `nom` | VARCHAR | Nom |
| `prenom` | VARCHAR | Prénom |
| `email` | VARCHAR | Email |
| `role` | VARCHAR | `user` ou `admin` |
| `status` | VARCHAR | `actif` ou bloqué |

---

## TRÈS IMPORTANT — La signification des états

C'est le point le plus important pour ta partie. Une tentative peut être dans quatre états, et tu dois savoir lesquels afficher et lesquels compter dans la moyenne.

| `etat_tentative` | `status` | Signification | À afficher dans l'historique ? | À compter dans la moyenne ? |
|---|---|---|---|---|
| `terminée` | `valide` | QCM complété normalement | Oui | **Oui** |
| `abandonnée` | `valide` | Arrêté volontairement (score 0) | Oui | À décider (voir plus bas) |
| `annulée` | `invalide` | Annulé pour triche (3 avertissements) | À décider | **Non** |
| `en_cours` | `valide` | Démarré mais pas encore fini | **Non** | **Non** |

### Le piège à éviter absolument

Si tu calcules la moyenne avec un simple `AVG(score)` sur toutes les tentatives, tu vas inclure :

- les abandons (score 0) qui vont faire chuter la moyenne injustement,
- les tentatives annulées pour triche,
- les tentatives encore en cours.

**Tu dois toujours filtrer par état.** En général, la moyenne ne se calcule que sur les tentatives `terminée`.

---

## Les requêtes types pour démarrer

### Historique d'un utilisateur

```sql
SELECT id_t, date, score, etat_tentative, temps_ecoule
FROM tentatives
WHERE id_user = ? AND etat_tentative = 'terminée'
ORDER BY date DESC;
```

### Moyenne d'un utilisateur (sur les tentatives terminées uniquement)

```sql
SELECT AVG(score) AS moyenne
FROM tentatives
WHERE id_user = ? AND etat_tentative = 'terminée';
```

### Classement des utilisateurs (par moyenne décroissante)

```sql
SELECT u.nom, u.prenom, AVG(t.score) AS moyenne, COUNT(t.id_t) AS nb_tentatives
FROM tentatives t
JOIN utilisateurs u ON t.id_user = u.id_user
WHERE t.etat_tentative = 'terminée'
GROUP BY t.id_user
ORDER BY moyenne DESC;
```

### Détail des réponses d'une tentative (pour stats ou correction d'historique)

```sql
SELECT r.reponse_user, q.question, q.bonne_reponse,
       q.reponse1, q.reponse2, q.reponse3, q.reponse4
FROM reponses r
JOIN questions q ON r.id_q = q.id_q
WHERE r.id_t = ?;
```

### Statistique : les questions les plus ratées (exemple de stat détaillée)

```sql
SELECT q.question,
       COUNT(*) AS nb_reponses,
       SUM(CASE WHEN r.reponse_user = q.bonne_reponse THEN 1 ELSE 0 END) AS nb_correctes
FROM reponses r
JOIN questions q ON r.id_q = q.id_q
GROUP BY q.id_q
ORDER BY (nb_correctes / nb_reponses) ASC;
```

---

## Comment retrouver l'utilisateur d'une réponse

La table `reponses` ne contient **pas** directement `id_user`. C'est volontaire (normalisation : on évite de dupliquer l'information). Pour retrouver l'utilisateur d'une réponse, on passe par la tentative :

```
réponse → sa tentative (id_t) → l'utilisateur de la tentative (id_user)
```

En SQL, avec une jointure :

```sql
SELECT u.nom, u.prenom, r.reponse_user
FROM reponses r
JOIN tentatives t ON r.id_t = t.id_t
JOIN utilisateurs u ON t.id_user = u.id_user
WHERE r.id_t = ?;
```

---

## À savoir sur certaines colonnes

- **`temps_ecoule` est en secondes.** Si tu veux l'afficher joliment (par exemple "2 min 15 s"), tu devras le convertir : minutes = `temps_ecoule / 60`, secondes = `temps_ecoule % 60`.
- **`score` est sur 20** (10 questions × 2 points). Le maximum est donc 20.
- **`date` est un DATETIME** (date + heure). Pour n'afficher que la date dans l'historique, tu peux formater avec `DATE_FORMAT(date, '%d/%m/%Y')` en SQL ou côté PHP.

---

## Les décisions à trancher avec l'équipe

Avant de coder, mets-toi d'accord avec l'équipe sur ces points :

1. **Les abandons (`abandonnée`, score 0)** : on les affiche dans l'historique ? On les compte dans la moyenne ? (Recommandation : les afficher, mais ne pas les compter dans la moyenne, sinon ça pénalise injustement.)
2. **Les tentatives annulées pour triche (`annulée`)** : on les montre dans l'historique avec une mention "invalidée", ou on les masque complètement ?
3. **L'export des résultats** : quel format ? (CSV est le plus simple à produire en PHP.)

---

## Sécurité à respecter (comme dans le reste du projet)

- **Toujours filtrer par `id_user`** pour qu'un utilisateur ne voie que ses propres tentatives (sauf pour le classement, qui est public).
- **Utiliser des requêtes préparées** (`prepare` + `bind_param`) partout, jamais de variables directement dans les requêtes (protection contre l'injection SQL).
- **Échapper l'affichage** avec `htmlspecialchars()` sur toute donnée affichée (protection XSS).
- **Vérifier la session** en haut de chaque page (utilisateur connecté), comme dans le reste de l'application.

---

## Connexion à la base

Tu peux réutiliser le fichier `db_modif.php` (déjà présent dans le projet) qui fournit la connexion via la variable `$conn` :

```php
require "db_modif.php";
// $conn est maintenant disponible
```

Base : `qqcm` — encodage `utf8mb4`.
