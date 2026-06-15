-- ----------------------------------------------------------
-- Script MYSQL pour mcd 
-- ----------------------------------------------------------


-- ----------------------------
-- Table: utilisateurs
-- ----------------------------
CREATE TABLE utilisateurs (
  id_user INT NOT NULL,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  email VARCHAR(50) NOT NULL,
  mot_de_passe VARCHAR(50) NOT NULL,
  role enum('admin','user') NOT NULL default 'user',
  status TEXT NOT NULL,
  CONSTRAINT utilisateurs_PK PRIMARY KEY (id_user)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: categories
-- ----------------------------
CREATE TABLE categories (
  id_categorie INT NOT NULL,
  CONSTRAINT categories_PK PRIMARY KEY (id_categorie)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: tentatives
-- ----------------------------
CREATE TABLE tentatives (
  id_t INT NOT NULL,
  score FLOAT NOT NULL,
  date DATETIME NOT NULL,
  temps_ecoule INT NOT NULL,
  etat_tentative TEXT NOT NULL,
  status TEXT NOT NULL,
  id_user INT NOT NULL,
  CONSTRAINT tentatives_PK PRIMARY KEY (id_t),
  CONSTRAINT tentatives_id_user_FK FOREIGN KEY (id_user) REFERENCES utilisateurs (id_user)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: Questions
-- ----------------------------
CREATE TABLE Questions (
  id_q INT NOT NULL,
  question TEXT NOT NULL,
  reponse1 VARCHAR(50) NOT NULL,
  reponse2 VARCHAR(50) NOT NULL,
  reponse3 VARCHAR(50) NOT NULL,
  reponse4 VARCHAR(50) NOT NULL,
  bonne_reponse INT NOT NULL,
  id_categorie INT NOT NULL,
  CONSTRAINT Questions_PK PRIMARY KEY (id_q),
  CONSTRAINT Questions_id_categorie_FK FOREIGN KEY (id_categorie) REFERENCES categories (id_categorie)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: reponses
-- ----------------------------
CREATE TABLE reponses (
  id_rep INT NOT NULL,
  reponse_user INT NOT NULL,
  id_q INT NOT NULL,
  id_t INT NOT NULL,
  CONSTRAINT reponses_PK PRIMARY KEY (id_rep),
  CONSTRAINT reponses_id_q_FK FOREIGN KEY (id_q) REFERENCES Questions (id_q),
  CONSTRAINT reponses_id_t_FK FOREIGN KEY (id_t) REFERENCES tentatives (id_t)
)ENGINE=InnoDB;