CREATE TABLE IF NOT EXISTS commandes_boutique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_commande VARCHAR(80) NOT NULL,
    nom_contact VARCHAR(160) NOT NULL,
    entreprise VARCHAR(180) DEFAULT NULL,
    email VARCHAR(190) NOT NULL,
    telephone VARCHAR(80) NOT NULL,
    adresse_livraison TEXT NOT NULL,
    commune VARCHAR(140) NOT NULL,
    ville VARCHAR(140) NOT NULL,
    indications TEXT DEFAULT NULL,
    total_estime DECIMAL(15,2) NOT NULL DEFAULT 0,
    statut VARCHAR(30) NOT NULL DEFAULT 'nouvelle',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_shop_order_reference (reference_commande),
    INDEX idx_shop_order_status_date (statut, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS commande_boutique_ligne (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_ligne DECIMAL(15,2) NOT NULL DEFAULT 0,
    INDEX idx_shop_order_line_order (commande_id),
    CONSTRAINT fk_shop_order_line_order FOREIGN KEY (commande_id) REFERENCES commandes_boutique(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
