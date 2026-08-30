ALTER TABLE offre ADD COLUMN IF NOT EXISTS client_id INT DEFAULT NULL AFTER id_offre;
ALTER TABLE offre ADD COLUMN IF NOT EXISTS date_limite_reponse DATE DEFAULT NULL AFTER date_offre;
ALTER TABLE offre ADD COLUMN IF NOT EXISTS statut VARCHAR(30) NOT NULL DEFAULT 'en_cours' AFTER date_limite_reponse;
ALTER TABLE offre ADD COLUMN IF NOT EXISTS motif_perte TEXT DEFAULT NULL AFTER statut;
ALTER TABLE offre ADD COLUMN IF NOT EXISTS fichier_ao VARCHAR(255) DEFAULT NULL AFTER motif_perte;
ALTER TABLE offre ADD COLUMN IF NOT EXISTS fichier_ao_original VARCHAR(255) DEFAULT NULL AFTER fichier_ao;
ALTER TABLE devis ADD COLUMN IF NOT EXISTS statut_devis VARCHAR(30) NOT NULL DEFAULT 'en_attente' AFTER validation_generale;
ALTER TABLE devis ADD COLUMN IF NOT EXISTS delai_livraison_jours INT DEFAULT NULL AFTER delai_livraison;

CREATE TABLE IF NOT EXISTS debourses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bon_commande_id INT NOT NULL,
    montant_fournitures DECIMAL(15,2) NOT NULL DEFAULT 0,
    montant_transport DECIMAL(15,2) NOT NULL DEFAULT 0,
    statut VARCHAR(30) NOT NULL DEFAULT 'brouillon',
    valide_le DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_debourse_bc (bon_commande_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS planning_affaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bon_commande_id INT NOT NULL,
    libelle VARCHAR(190) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_planning_bc_start (bon_commande_id, date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fiches_expression_besoin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bon_commande_id INT NOT NULL,
    numero_feb VARCHAR(80) NOT NULL,
    date_disponibilite DATE NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'a_analyser',
    montant_demande DECIMAL(15,2) NOT NULL DEFAULT 0,
    motif_rejet TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_feb_bc (bon_commande_id),
    UNIQUE KEY uniq_numero_feb (numero_feb)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS decaissements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feb_id INT NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    date_decaissement DATE NOT NULL,
    reference_paiement VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_decaissement_feb (feb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS factures_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bon_commande_id INT NOT NULL,
    numero_facture VARCHAR(100) NOT NULL,
    date_facture DATE NOT NULL,
    date_echeance DATE NOT NULL,
    montant_ttc DECIMAL(15,2) NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'emise',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_facture_bc (bon_commande_id),
    UNIQUE KEY uniq_numero_facture (numero_facture)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS encaissements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facture_id INT NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    date_encaissement DATE NOT NULL,
    reference_paiement VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_encaissement_facture (facture_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
