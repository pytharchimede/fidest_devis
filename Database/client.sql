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
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `id_client` int(11) NOT NULL,
  `code_client` text NOT NULL,
  `nom_client` text NOT NULL,
  `localisation_client` text NOT NULL,
  `commune_client` text NOT NULL,
  `bp_client` text NOT NULL,
  `pays_client` text NOT NULL,
  `date_creat_client` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `client`
--

INSERT INTO `client` (`id_client`, `code_client`, `nom_client`, `localisation_client`, `commune_client`, `bp_client`, `pays_client`, `date_creat_client`) VALUES
(1, 'FID-CLI-001', 'SIFCA', 'Bd. du Havre, Vers les Grands Moulins', 'Zone portuaire, Treichville', '01 BP 1289 Abidjan 01', 'Cote d Ivoire', 0),
(2, 'FID-CLI-002', 'BIA', 'Sur le VGE, a cote d\'Orange CI', 'Marcory - 18 BP 1081 Abidjan 18', '01 BP 1289 Abidjan 01', 'Cote d Ivoire', 0),
(3, 'FID-CLI-003', 'CIPREL', 'A côté de CIE,', 'Vridi Port-Bouet', '(+225) 27 21 23 63 38', 'Cote d Ivoire', 0),
(4, 'FID-CLI-004', 'FOXTROT', 'Rue des Pétroliers, face à VIVO-ENERGIE', 'Zone Industrielle Vridi Port-Bouet', '15 BP 324 Abidjan 15 Abidjan', 'Cote d Ivoire', 0),
(5, 'FID-CLI-005', 'SAPH', 'Rue des Galions Immeuble EX-SIT', 'Zone portuaire Treichville', '01 BP 1322 ABIDJAN 01 Abidjan', 'Cote d Ivoire', 0),
(6, 'FID-CLI-006', 'SICMA', 'RESIDENCE ELYAD PROXIMITE DU TRIBUNAL DU COMMERCE', 'DEUX PLATEAU', '04BP225 ABIDJAN', 'COTE D\'IVOIRE', 2024),
(7, 'FID-CLI-006', 'SICMA', 'RESIDENCE ELYAD PROXIMITE DU TRIBUNAL DU COMMERCE', 'DEUX PLATEAU', '04BP225 ABIDJAN', 'COTE D\'IVOIRE', 2024),
(8, 'FID-CLI-007', 'DJERA SERVICES', ' Bd. de Marseille, à côté de chez Paul, immeuble Panorama, au 2ème étage ', 'marcory', ' 25 BP 2249 Abidjan 25', 'Côte d’Ivoire', 2024),
(9, 'FID-CLI-008', 'PALMCI', ' Boulevard de Vridi, zone portuaire  ', 'TREICHVILLE', '18 BP 3321 Abidjan 18 ', 'Côte d’Ivoire', 2024),
(10, 'FID-CLI-009', 'SMB', 'Abidjan Vridi - Boulevard de Petit–Bassam', 'VRIDI', '12 BP 622 Abidjan 12', 'COTE D\'IVOIRE', 2024),
(11, 'FID-CLI-010', 'SAEPP', 'Rue des Pétroliers - Zone Industrielle Vridi .', 'PORT BOUET', '12 BP 737 Abidjan 12.', 'Côte d\'Ivoire. ', 2024),
(12, 'FID-CLI-011', 'ERANOV', 'Avenue Franchet d\'Esperey, Immeuble Ollo, Bâtiment B – 1er étage ', 'Plateau', '17 BP 36 Abidjan 17', 'Côte d’Ivoire', 2024),
(13, 'FID-CLI-011', 'ERANOVE', 'Avenue Franchet d\'Esperey, Immeuble Ollo, Bâtiment B – 1er étage ', 'Plateau', '17 BP 36 Abidjan 17', 'Côte d’Ivoire', 2024),
(14, 'FID-CLI-012', 'TOTAL ENERGIES CI', 'Immeuble Rive Gauche 100, rue des brasseurs ', '- Zone 3 ', '01 BP 336 ABIDJAN 01', 'Côte d’Ivoire', 2024),
(15, 'FID-CLI-013', 'ABY CONCEPT', 'Ambassades angle rue booker washington et du Bd Hassan II', 'Cocody ', '06 BP Abidjan 06', 'Côte d\'Ivoire', 2024),
(16, 'FID-CLI-013', 'O.M.S', 'DEPAETEMENT LEPRE', 'abidjan', 'département lèpre', 'Côte d’Ivoire', 2024),
(17, 'FID-CLI-013', 'O.M.S', 'DEPAETEMENT LEPRE', 'abidjan', 'département lèpre', 'Côte d’Ivoire', 2024),
(18, 'FID-CLI-013', 'O.M.S', 'En face de la grande mosquée - Deux-plateaux - Aghien', 'COCODI', ' 01 BP 2494 Abidjan 01', 'Côte d’Ivoire', 2024),
(19, 'FID-CLI-14', 'MR .ATCHUI', 'ABIDJAN', 'X', '111', 'Côte d’Ivoire', 2024),
(20, 'FID-CLI-011', 'ERANOVE-CI', 'Boulevard Botreau Roussel', 'Immeuble crrae-umoa', 'aile 5c  01BP 6222 Abidjan 01.', 'Côte d’Ivoire', 2024),
(21, 'FID-CLI-012', 'INDUSTRIAL PROTECT', 'ABIDJAN', 'Abidjan ', 'ABIDJAN', 'Côte d\'Ivoire', 2024),
(22, 'FID-CLIC-014 ', 'Compagnie Minière Bafing  ( CMB ) ', 'Rue du canal,immeuble le Meridian,_8èm étage  rue G56', 'Abidjan ', '000225', 'Cote d\'Ivoire ', 2025),
(23, 'FID-CLI-014', 'SICE SRL', 'Via G. Bartolucci,', 'Borgo Santa Maria (PU) ', '61122 ', 'ITALY', 2025),
(24, 'FID-CLI-015', 'GESTOCI', 'Bd. de Vridi, Rue Sylvestre - Zone Industrielle Vridi', 'Vridi Port-Bouet - ', '15 BP 89 Abidjan 15', 'COTE D\'IVOIRE', 2025);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_client`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
