DROP TABLE IF EXISTS `amis`;
CREATE TABLE IF NOT EXISTS `amis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dateamitie` datetime NOT NULL,
  `idutilisateur1` int NOT NULL,
  `idutilisateur2` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idutilisateur1` (`idutilisateur1`),
  KEY `idutilisateur2` (`idutilisateur2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `demandeami`;
CREATE TABLE IF NOT EXISTS `demandeami` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datedemande` datetime NOT NULL,
  `idtransmetteur` int NOT NULL,
  `idrecepteur` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idtransmetteur` (`idtransmetteur`),
  KEY `idrecepteur` (`idrecepteur`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `question`;
CREATE TABLE IF NOT EXISTS `question` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sujet` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idquiz` int NOT NULL,
  `media` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chemin relatif vers le fichier média (ex: uploads/img_abc123.jpg)',
  `media_type` enum('image','audio','video') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type de média attaché à la question',
  PRIMARY KEY (`id`),
  KEY `idquiz` (`idquiz`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `question` (`id`, `sujet`, `idquiz`, `media`, `media_type`) VALUES
(17, 'Quelle est la capitale de la France', 2, NULL, NULL),
(24, 'En quelle année la france a gagné la cdm', 1, NULL, NULL),
(25, 'Qu&#039;entends tu ici', 1, 'uploads/c9ae4f686b2079d5c3d279c96be46934.mp3', 'audio'),
(23, 'Qui est le meilleur buteur masculin', 1, NULL, NULL),
(28, 'Que traduit ce meme', 3, 'uploads/5ad5f9db7f11a40ac61b5fde8894fe9e.mp4', 'video');



DROP TABLE IF EXISTS `quiz`;
CREATE TABLE IF NOT EXISTS `quiz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('Aucune','Sport','Culture générale','Divertissement') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `difficulte` int DEFAULT NULL,
  `idutilisateur` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idutilisateur` (`idutilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quiz` (`id`, `titre`, `categorie`, `difficulte`, `idutilisateur`) VALUES
(1, 'Football', 'Sport', 3, 1),
(2, 'Capitales pays', 'Aucune', 3, 1),
(3, 'Meme translation', 'Divertissement', 3, 1);



DROP TABLE IF EXISTS `reponse`;
CREATE TABLE IF NOT EXISTS `reponse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contenu` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estvraie` tinyint(1) NOT NULL,
  `idquestion` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idquestion` (`idquestion`)
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reponse` (`id`, `contenu`, `estvraie`, `idquestion`) VALUES
(99, 'Ruuuuuuuuurrrr', 0, 25),
(98, 'Muuuuuuuuurrrrr', 0, 25),
(97, 'Siuuuuuuuuurrr', 1, 25),
(67, 'Lille', 0, 17),
(66, 'Toulouse', 0, 17),
(96, '2022', 0, 24),
(95, '2014', 0, 24),
(65, 'Paris', 1, 17),
(94, '2006', 0, 24),
(93, '1998', 1, 24),
(92, 'Pele', 0, 23),
(68, 'Angers', 0, 17),
(91, 'Ronaldinho', 0, 23),
(90, 'Cristiano Ronaldo', 1, 23),
(89, 'Lionel Messi', 0, 23),
(100, 'Duuuuuuuuurrrrr', 0, 25),
(112, 'Tu es assez fou', 0, 28),
(111, 'Tu es gentil', 0, 28),
(110, 'Tu as un air bizarre', 0, 28),
(109, 'Tu as un air suspcieux', 1, 28);



DROP TABLE IF EXISTS `resultatquiz`;
CREATE TABLE IF NOT EXISTS `resultatquiz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `score` int NOT NULL,
  `idutilisateur` int NOT NULL,
  `idquiz` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idquiz` (`idquiz`),
  KEY `idutilisateur` (`idutilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `resultatquiz` (`id`, `score`, `idutilisateur`, `idquiz`) VALUES
(1, 100, 1, 1),
(2, 200, 1, 1),
(3, 100, 1, 1),
(4, 200, 1, 1),
(5, 100, 1, 1),
(6, 200, 1, 1),
(7, 100, 1, 2),
(8, 300, 1, 1),
(9, 300, 1, 1),
(10, 300, 1, 1),
(11, 100, 1, 3);



DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `identifiant` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mdp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `utilisateur` (`id`, `identifiant`, `mdp`) VALUES
(1, 'Admin_Systeme', '$2y$10$6GlTYUhoXQAua/Uv3rcbx.4gwTYK2ON6YPgCGDiu2JwU2AJ/vECfW');
COMMIT;
