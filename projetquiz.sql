-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 18 mai 2026 à 11:37
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projetquiz`
--

-- --------------------------------------------------------

--
-- Structure de la table `amis`
--

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

-- --------------------------------------------------------

--
-- Structure de la table `demandeami`
--

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

-- --------------------------------------------------------

--
-- Structure de la table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE IF NOT EXISTS `question` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sujet` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idquiz` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idquiz` (`idquiz`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quiz`
--

DROP TABLE IF EXISTS `quiz`;
CREATE TABLE IF NOT EXISTS `quiz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('Aucune','Sport','Culture générale','Divertissement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `difficulte` int DEFAULT NULL,
  `idutilisateur` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idutilisateur` (`idutilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reponse`
--

DROP TABLE IF EXISTS `reponse`;
CREATE TABLE IF NOT EXISTS `reponse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contenu` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estvraie` tinyint(1) NOT NULL,
  `idquestion` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idquestion` (`idquestion`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `resultatquiz`
--

DROP TABLE IF EXISTS `resultatquiz`;
CREATE TABLE IF NOT EXISTS `resultatquiz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `score` int NOT NULL,
  `idutilisateur` int NOT NULL,
  `idquiz` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idquiz` (`idquiz`),
  KEY `idutilisateur` (`idutilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `identifiant` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mdp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
