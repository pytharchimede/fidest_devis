-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : jeu. 15 mai 2025 à 12:34
-- Version du serveur : 10.11.11-MariaDB-cll-lve
-- Version de PHP : 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `fidestci_app_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `user_devis`
--

CREATE TABLE `user_devis` (
  `id` int(11) NOT NULL,
  `mail_pro` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `modifier_devis` tinyint(1) DEFAULT 0,
  `visualiser_devis` tinyint(1) DEFAULT 0,
  `soumettre_devis` tinyint(1) DEFAULT 0,
  `masquer_devis` tinyint(1) DEFAULT 0,
  `envoyer_devis` tinyint(1) DEFAULT 0,
  `valider_devis` tinyint(1) DEFAULT 0,
  `gestion_utilisateur` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `active` int(11) NOT NULL,
  `photo` text NOT NULL,
  `signature` text NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `user_devis`
--

INSERT INTO `user_devis` (`id`, `mail_pro`, `password`, `nom`, `prenom`, `modifier_devis`, `visualiser_devis`, `soumettre_devis`, `masquer_devis`, `envoyer_devis`, `valider_devis`, `gestion_utilisateur`, `created_at`, `active`, `photo`, `signature`, `role_id`) VALUES
(1, 'daniel@fidest.org', 'c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec', 'N\'yaba', 'Daniel', 1, 1, 1, 1, 1, 1, 0, '2024-11-18 10:46:01', 1, '', '', 1),
(2, 'ulrich@banamur.com', 'bbaa7aa4a831491e8736f1c10cba45668c1c7f1e86a5495c51f7f65393db225afbcc87e5198f6e9ad0c7705b6f0df3f315bd4f5f9eb3a55853c78a2d2d04f67c', 'Amani', 'Ulrich', 1, 1, 1, 1, 1, 1, 0, '2024-11-18 10:46:01', 1, 'photo/ma_photo.jpg', '', 4),
(3, 'amichia@fidest.org', 'c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec', 'KANE', 'Amichia', 1, 1, 1, 1, 1, 1, 0, '2024-11-18 10:46:01', 1, '', 'sign_amichia.jpg', 2),
(4, 'braud@fidest.org', 'c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec', 'BRAUD', 'Alex', 1, 1, 1, 1, 1, 1, 0, '2024-11-18 10:46:01', 1, '', 'sign_braud.jpg', 3);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `user_devis`
--
ALTER TABLE `user_devis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mail_pro` (`mail_pro`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `user_devis`
--
ALTER TABLE `user_devis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
