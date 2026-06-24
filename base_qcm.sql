-- ==========================================================
-- SCRIPT SQL COMPLET : STRUCTURE OPTIMISÉE + JEU DE DONNÉES
-- ==========================================================

DROP DATABASE IF EXISTS QQCM;
CREATE DATABASE QQCM;
USE QQCM;

-- ----------------------------
-- Table: utilisateurs
-- ----------------------------
CREATE TABLE utilisateurs (
  id_user INT NOT NULL AUTO_INCREMENT,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,          -- UNIQUE évite les doublons d'inscription
  mot_de_passe VARCHAR(255) NOT NULL,        -- Idéal pour stocker des mots de passe hachés sécurisés
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  status VARCHAR(50) NULL,
  CONSTRAINT utilisateurs_PK PRIMARY KEY (id_user)
) ENGINE=InnoDB;


-- ----------------------------
-- Table: categories
-- ----------------------------
CREATE TABLE categories (
  id_categorie INT NOT NULL AUTO_INCREMENT,
  nom_categorie VARCHAR(100) NOT NULL,       -- La colonne manquante essentielle pour nommer la catégorie !
  CONSTRAINT categories_PK PRIMARY KEY (id_categorie)
) ENGINE=InnoDB;


-- ----------------------------
-- Table: tentatives
-- ----------------------------
CREATE TABLE tentatives (
  id_t INT NOT NULL AUTO_INCREMENT,
  score FLOAT NOT NULL,
  date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Enregistre la date automatiquement
  temps_ecoule INT NOT NULL,                 -- Temps passé en secondes
  etat_tentative VARCHAR(50) NOT NULL,       -- ex: 'En cours', 'Terminé'
  status VARCHAR(50) NULL,
  id_user INT NOT NULL,
  CONSTRAINT tentatives_PK PRIMARY KEY (id_t),
  CONSTRAINT tentatives_id_user_FK FOREIGN KEY (id_user) REFERENCES utilisateurs (id_user) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ----------------------------
-- Table: Questions
-- ----------------------------
CREATE TABLE Questions (
  id_q INT NOT NULL AUTO_INCREMENT,
  question TEXT NOT NULL,
  reponse1 VARCHAR(255) NOT NULL,            -- Augmenté à 255 caractères pour ne pas couper tes phrases de réponse
  reponse2 VARCHAR(255) NOT NULL,
  reponse3 VARCHAR(255) NOT NULL,
  reponse4 VARCHAR(255) NOT NULL,
  bonne_reponse INT NOT NULL,                -- Chiffre de 1 à 4 indiquant la bonne colonne
  id_categorie INT NOT NULL,
  CONSTRAINT Questions_PK PRIMARY KEY (id_q),
  CONSTRAINT Questions_id_categorie_FK FOREIGN KEY (id_categorie) REFERENCES categories (id_categorie) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ----------------------------
-- Table: reponses
-- ----------------------------
CREATE TABLE reponses (
  id_rep INT NOT NULL AUTO_INCREMENT,
  reponse_user INT NOT NULL,                 -- Numéro choisi par l'utilisateur (1 à 4)
  id_q INT NOT NULL,
  id_t INT NOT NULL,
  CONSTRAINT reponses_PK PRIMARY KEY (id_rep),
  CONSTRAINT reponses_id_q_FK FOREIGN KEY (id_q) REFERENCES Questions (id_q) ON DELETE CASCADE,
  CONSTRAINT reponses_id_t_FK FOREIGN KEY (id_t) REFERENCES tentatives (id_t) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- INSERTION DES 5 CATÉGORIES
-- ==========================================================
INSERT INTO categories (nom_categorie) VALUES 
('Informatique & Web'),
('Histoire & Géographie'),
('Sciences & Nature'),
('Culture Générale'),
('Pop Culture & Cinéma');


-- ==========================================================
-- INSERTION DES 100 QUESTIONS
-- ==========================================================

-- --- CATÉGORIE 1 : INFORMATIQUE & WEB (ID 1) ---
INSERT INTO Questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie) VALUES 
('Que signifie le sigle HTML ?', 'Hyper Text Markup Language', 'High Text Mobile Language', 'Hyper Tech Multi Language', 'Hyperlink Text Management Language', 1, 1),
('Quel langage gère le style d''un site web ?', 'PHP', 'SQL', 'CSS', 'JavaScript', 3, 1),
('En quelle année est né JavaScript ?', '1990', '1995', '2000', '2005', 2, 1),
('Quel protocole est sécurisé pour le web ?', 'HTTP', 'FTP', 'HTTPS', 'SMTP', 3, 1),
('Que signifie le "P" dans PHP ?', 'Preprocesseur', 'Program', 'Protocol', 'Page', 1, 1),
('Quel est le principal moteur de recherche au monde ?', 'Bing', 'Yahoo', 'Google', 'DuckDuckGo', 3, 1),
('Quel symbole utilise-t-on pour un ID en CSS ?', '.', '#', '$', '@', 2, 1),
('Quel composant est le "cerveau" d''un ordinateur ?', 'Disque dur', 'Carte graphique', 'RAM', 'Processeur (CPU)', 4, 1),
('Quelle balise HTML définit le titre principal ?', '<h6>', '<p>', '<h1>', '<title>', 3, 1),
('Que signifie SQL ?', 'Structured Query Language', 'Simple Question Language', 'Sequential Query List', 'Strong Query Loop', 1, 1),
('Quel type de mémoire s''efface à l''extinction ?', 'ROM', 'RAM', 'SSD', 'Disque dur', 2, 1),
('Quel système d''exploitation utilise un pingouin comme mascotte ?', 'Windows', 'macOS', 'Linux', 'Android', 3, 1),
('Quel est le format d''image standard avec transparence ?', 'JPEG', 'BMP', 'PNG', 'TIFF', 3, 1),
('Que signifie IP dans "Adresse IP" ?', 'Internet Protocol', 'Intranet Path', 'Internal Packet', 'Instant Power', 1, 1),
('Qui a cofondé Microsoft avec Paul Allen ?', 'Steve Jobs', 'Mark Zuckerberg', 'Bill Gates', 'Elon Musk', 3, 1),
('Quel langage est principalement utilisé côté serveur ?', 'HTML', 'CSS', 'Node.js', 'Sass', 3, 1),
('Quelle entreprise a développé le framework React ?', 'Google', 'Microsoft', 'Apple', 'Meta (Facebook)', 4, 1),
('Quel port utilise habituellement le protocole HTTP ?', '21', '80', '443', '22', 2, 1),
('Que signifie "CMS" (ex: WordPress) ?', 'Computer Management System', 'Content Management System', 'Core Memory System', 'Creative Media Software', 2, 1),
('Quelle méthode HTTP envoie des données sensibles (ex: mot de passe) ?', 'GET', 'POST', 'PUT', 'DELETE', 2, 1);

-- --- CATÉGORIE 2 : HISTOIRE & GÉOGRAPHIE (ID 2) ---
INSERT INTO Questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie) VALUES 
('Quelle est la capitale de l''Australie ?', 'Sydney', 'Melbourne', 'Canberra', 'Brisbane', 3, 2),
('En quelle année a eu lieu la Révolution Française ?', '1492', '1789', '1815', '1914', 2, 2),
('Quel est le plus long fleuve du monde ?', 'Le Mississippi', 'L''Amazone', 'Le Nil', 'Le Danube', 2, 2),
('Qui était le premier président des États-Unis ?', 'Thomas Jefferson', 'Abraham Lincoln', 'George Washington', 'John Adams', 3, 2),
('Quel pays a la plus grande superficie ?', 'Canada', 'Chine', 'États-Unis', 'Russie', 4, 2),
('En quelle année a commencé la Première Guerre mondiale ?', '1914', '1918', '1939', '1945', 1, 2),
('Quel océan borde la côte Ouest des États-Unis ?', 'Océan Atlantique', 'Océan Indien', 'Océan Pacifique', 'Océan Arctique', 3, 2),
('Quel empereur romain a été assassiné aux ides de Mars ?', 'Auguste', 'Néron', 'Caligula', 'Jules César', 4, 2),
('Quel est le plus petit pays du monde ?', 'Monaco', 'Le Vatican', 'Saint-Marin', 'Malte', 2, 2),
('Qui a découvert l''Amérique en 1492 ?', 'Vasco de Gama', 'Magellan', 'Christophe Colomb', 'Marco Polo', 3, 2),
('Dans quel pays se trouve le désert du Sahara ?', 'Brésil', 'Australie', 'Algérie', 'Inde', 3, 2),
('Quelle est la capitale du Japon ?', 'Kyoto', 'Osaka', 'Séoul', 'Tokyo', 4, 2),
('Qui était surnommé le "Roi-Soleil" ?', 'Louis XIV', 'Louis XVI', 'Henri IV', 'Charlemagne', 1, 2),
('Quel fleuve traverse Paris ?', 'La Loire', 'Le Rhône', 'La Seine', 'La Garonne', 3, 2),
('Quel pays européen a pour capitale Lisbonne ?', 'Espagne', 'Italie', 'Grèce', 'Portugal', 4, 2),
('Quelle muraille célèbre se trouve en Asie ?', 'La Muraille de Chine', 'Le Mur de Berlin', 'Le Mur d''Hadrien', 'Le Mur des Lamentations', 1, 2),
('En quelle année est tombé le mur de Berlin ?', '1968', '1985', '1989', '1991', 3, 2),
('Quelle est la capitale du Canada ?', 'Toronto', 'Montréal', 'Ottawa', 'Vancouver', 3, 2),
('Quel peuple a construit les pyramides de Gizeh ?', 'Les Romains', 'Les Égyptiens', 'Les Grecs', 'Les Mayas', 2, 2),
('Quelle mer sépare l''Europe de l''Afrique ?', 'La Mer Morte', 'La Mer Noire', 'La Mer Rouge', 'La Mer Méditerranée', 4, 2);

-- --- CATÉGORIE 3 : SCIENCES & NATURE (ID 3) ---
INSERT INTO Questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie) VALUES 
('Quelle est la formule chimique de l''eau ?', 'HO2', 'H2O', 'CO2', 'O2', 2, 3),
('Quelle planète est surnommée la planète rouge ?', 'Vénus', 'Mars', 'Jupiter', 'Saturne', 2, 3),
('Quel est l''organe le plus lourd du corps humain ?', 'Le cerveau', 'Le foie', 'La peau', 'Le cœur', 3, 3),
('Quelle vitesse correspond environ à la vitesse de la lumière ?', '300 000 km/s', '1 200 km/h', '150 000 km/s', '3 000 km/s', 1, 3),
('Quel gaz les plantes absorbent-elles pendant la journée ?', 'Oxygène', 'Azote', 'Dioxyde de carbone', 'Hydrogène', 3, 3),
('Quel est le plus grand mammifère du monde ?', 'L''éléphant d''Afrique', 'Le requin baleine', 'La baleine bleue', 'Le diplodocus', 3, 3),
('Combien d''os compte un corps humain adulte ?', '106', '206', '306', '406', 2, 3),
('Quelle est la principale source d''énergie de la Terre ?', 'Le vent', 'Le Soleil', 'Le noyau terrestre', 'La Lune', 2, 3),
('Quelle est la température d''ébullition de l''eau à pression normale ?', '50°C', '80°C', '100°C', '120°C', 3, 3),
('Quel métal est liquide à température ambiante ?', 'Le plomb', 'Le mercure', 'Le cuivre', 'L''étain', 2, 3),
('Quelle force nous retient au sol ?', 'La force magnétique', 'La force centrifuge', 'La gravité', 'La friction', 3, 3),
('Quel est l''élément le plus abondant dans l''univers ?', 'L''oxygène', 'L''hydrogène', 'Le carbone', 'L''hélium', 2, 3),
('Combien de cœurs possède une pieuvre ?', '1', '2', '3', '4', 3, 3),
('Quelle science étudie les tremblements de terre ?', 'La météorologie', 'La sismologie', 'La géologie', 'L''astronomie', 2, 3),
('Quel animal est connu pour être le plus rapide au monde ?', 'Le guépard', 'Le lion', 'Le lièvre', 'L''autruche', 1, 3),
('Quel est le composant principal de l''air ?', 'L''oxygène', 'Le dioxyde de carbone', 'L''azote', 'L''argon', 3, 3),
('Quel est le groupe sanguin universel donneur ?', 'A+', 'B-', 'AB+', 'O-', 4, 3),
('Quelle planète est la plus proche du Soleil ?', 'Terre', 'Vénus', 'Mercure', 'Mars', 3, 3),
('Quelle substance donne sa couleur verte aux plantes ?', 'La sève', 'La chlorophylle', 'Le glucose', 'Le carotène', 2, 3),
('Combien de paires de chromosomes possède l''être humain ?', '20', '22', '23', '46', 3, 3);

-- --- CATÉGORIE 4 : CULTURE GÉNÉRALE (ID 4) ---
INSERT INTO Questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie) VALUES 
('Combien de touches trouve-t-on sur un piano standard ?', '64', '76', '88', '92', 3, 4),
('Qui a peint la célèbre "Joconde" ?', 'Claude Monet', 'Vincent van Gogh', 'Léonard de Vinci', 'Pablo Picasso', 3, 4),
('Quel est le livre le plus vendu au monde ?', 'Le Petit Prince', 'La Bible', 'Harry Potter', 'Don Quichotte', 2, 4),
('Dans quelle ville se trouve la tour Eiffel ?', 'Londres', 'Paris', 'New York', 'Berlin', 2, 4),
('Quelle est la monnaie officielle du Japon ?', 'Le Yuan', 'Le Dollar', 'Le Won', 'Le Yen', 4, 4),
('Combien de couleurs y a-t-il dans un arc-en-ciel ?', '5', '6', '7', '8', 3, 4),
('Quelle langue est la plus parlée nativement au monde ?', 'L''anglais', 'L''espagnol', 'Le chinois mandarin', 'L''hindi', 3, 4),
('Quel est le plus grand océan du monde ?', 'Océan Atlantique', 'Océan Indien', 'Océan Pacifique', 'Océan Arctique', 3, 4),
('Quel écrivain a créé le personnage de Sherlock Holmes ?', 'Agatha Christie', 'Arthur Conan Doyle', 'Jules Verne', 'Edgar Allan Poe', 2, 4),
('Quelle est la capitale de l''Italie ?', 'Milan', 'Venise', 'Florence', 'Rome', 4, 4),
('Qui a écrit "Les Misérables" ?', 'Émile Zola', 'Victor Hugo', 'Gustave Flaubert', 'Albert Camus', 2, 4),
('Quel jeu de société comporte des cases "Prison" et "Chance" ?', 'Le Scrabble', 'Le Monopoly', 'Le Cluedo', 'La Bonne Paye', 2, 4),
('Quel pays a inventé les Jeux Olympiques ?', 'L''Italie', 'L''Égypte', 'La Grèce', 'La France', 3, 4),
('Quel instrument possède 4 cordes et se joue avec un archet ?', 'La guitare', 'Le violon', 'La harpe', 'Le piano', 2, 4),
('Quel est le plus grand pays d''Afrique par sa superficie ?', 'Le Nigeria', 'L''Égypte', 'L''Algérie', 'L''Afrique du Sud', 3, 4),
('Quelle est la boisson la plus consommée au monde après l''eau ?', 'Le café', 'Le thé', 'Le Coca-Cola', 'La bière', 2, 4),
('Quelle est la capitale de l''Espagne ?', 'Barcelone', 'Séville', 'Madrid', 'Valence', 3, 4),
('Qui est le dieu de la foudre dans la mythologie grecque ?', 'Poséidon', 'Hadès', 'Zeus', 'Apollon', 3, 4),
('Combien d''États compte les États-Unis d''Amérique ?', '48', '49', '50', '52', 3, 4),
('Quelle fête célèbre-t-on le 25 décembre ?', 'Pâques', 'Halloween', 'Noël', 'Le Nouvel An', 3, 4);

-- --- CATÉGORIE 5 : POP CULTURE & CINÉMA (ID 5) ---
INSERT INTO Questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, id_categorie) VALUES 
('Quel est le nom du sorcier à lunettes créé par J.K. Rowling ?', 'Percy Jackson', 'Harry Potter', 'Ron Weasley', 'Frodon Sacquet', 2, 5),
('Quel super-héros porte un costume rouge avec une araignée noire ?', 'Batman', 'Superman', 'Spider-Man', 'Iron Man', 3, 5),
('Quel film de James Cameron se déroule sur la planète Pandora ?', 'Titanic', 'Aliens', 'Terminator', 'Avatar', 4, 5),
('Dans Star Wars, qui est le père de Luke Skywalker ?', 'Obi-Wan Kenobi', 'Yoda', 'Darth Vader', 'Han Solo', 3, 5),
('Quelle série Netflix met en scène le jeu de la roulette d''un point de vue mortel ?', 'Stranger Things', 'Squid Game', 'La Casa de Papel', 'Lupin', 2, 5),
('Quel est le nom du royaume dans "La Reine des Neiges" ?', 'Arendelle', 'Corona', 'Atlantica', 'DunBroch', 1, 5),
('Qui joue le rôle d''Iron Man dans le MCU ?', 'Chris Evans', 'Robert Downey Jr.', 'Chris Hemsworth', 'Tom Holland', 2, 5),
('De quel groupe de rock Freddie Mercury était-il le chanteur ?', 'The Beatles', 'Pink Floyd', 'Led Zeppelin', 'Queen', 4, 5),
('Quel personnage de jeu vidéo est un plombier moustachu vêtu de rouge ?', 'Luigi', 'Sonic', 'Mario', 'Link', 3, 5),
('Quel est le nom de l''anneau unique dans Le Seigneur des Anneaux ?', 'L''anneau de feu', 'L''anneau de Sauron', 'Le précieux', 'L''anneau de pouvoir', 3, 5),
('Quelle chanteuse est connue sous le nom de "Queen Bey" ?', 'Rihanna', 'Beyoncé', 'Lady Gaga', 'Taylor Swift', 2, 5),
('Dans quelle ville fictive vit Batman ?', 'Metropolis', 'Gotham City', 'Central City', 'Star City', 2, 5),
('Quel acteur incarne Jack Sparrow dans Pirates des Caraïbes ?', 'Brad Pitt', 'Leonardo DiCaprio', 'Johnny Depp', 'Tom Cruise', 3, 5),
('Quel est le nom de l''école de magie dans Harry Potter ?', 'Poudlard', 'Beauxbâtons', 'Durmstrang', 'Ilvermorny', 1, 5),
('Quel monstre géant japonais est surnommé le "Roi des Monstres" ?', 'King Kong', 'Godzilla', 'Mothra', 'Gamera', 2, 5),
('Quelle application de vidéos courtes a une note de musique pour logo ?', 'Instagram', 'Snapchat', 'TikTok', 'YouTube', 3, 5),
('Quel super-héros du MCU manie un marteau magique appelé Mjolnir ?', 'Iron Man', 'Hulk', 'Captain America', 'Thor', 4, 5),
('Dans la série Friends, combien d''amis principaux y a-t-il ?', '4', '5', '6', '7', 3, 5),
('Quel studio d''animation a créé les films Toy Story et Cars ?', 'Disney', 'DreamWorks', 'Pixar', 'Studio Ghibli', 3, 5),
('Quel est le nom du réseau social d''Elon Musk anciennement appelé Twitter ?', 'Threads', 'X', 'Meta', 'TikTok', 2, 5);

COMMIT;