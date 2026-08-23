-- Table produits pour futur e-commerce FIDEST
CREATE TABLE IF NOT EXISTS `produit` (
  `id_produit` INT NOT NULL AUTO_INCREMENT,
  `designation` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produit`),
  UNIQUE KEY `uniq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Table d'images liées aux produits
CREATE TABLE IF NOT EXISTS `produit_image` (
  `id_image` INT NOT NULL AUTO_INCREMENT,
  `produit_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `alt` VARCHAR(255) DEFAULT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `date_upload` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_image`),
  KEY `idx_produit` (`produit_id`),
  CONSTRAINT `fk_image_produit` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id_produit`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
