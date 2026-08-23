-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : jeu. 15 mai 2025 à 12:33
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
-- Structure de la table `role_devis`
--

CREATE TABLE `role_devis` (
  `id_role_devis` int(11) NOT NULL,
  `lib_role_devis` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `role_devis`
--

INSERT INTO `role_devis` (`id_role_devis`, `lib_role_devis`) VALUES
(1, 'commercial'),
(2, 'directeur commercial'),
(3, 'directeur general'),
(4, 'informaticien');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `role_devis`
--
ALTER TABLE `role_devis`
  ADD PRIMARY KEY (`id_role_devis`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `role_devis`
--
ALTER TABLE `role_devis`
  MODIFY `id_role_devis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
