CREATE TABLE IF NOT EXISTS quote_offer_reservations (
    offer_id INT NOT NULL PRIMARY KEY,
    quote_id INT DEFAULT NULL UNIQUE,
    reserved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO quote_offer_reservations (offer_id, quote_id)
SELECT offre_id, MIN(id) FROM devis WHERE offre_id > 0 GROUP BY offre_id;

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS archive_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exercise_start_date DATE NOT NULL,
    previous_start_date DATE DEFAULT NULL,
    archived_offers INT NOT NULL DEFAULT 0,
    archived_quotes INT NOT NULL DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_exercise_start (exercise_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE offre ADD COLUMN IF NOT EXISTS archived_at DATETIME DEFAULT NULL;
ALTER TABLE offre ADD COLUMN IF NOT EXISTS archive_run_id INT DEFAULT NULL;
ALTER TABLE devis ADD COLUMN IF NOT EXISTS archived_at DATETIME DEFAULT NULL;
ALTER TABLE devis ADD COLUMN IF NOT EXISTS archive_run_id INT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS archive_offre LIKE offre;
ALTER TABLE archive_offre ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_devis LIKE devis;
ALTER TABLE archive_devis ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_ligne_devis LIKE ligne_devis;
ALTER TABLE archive_ligne_devis ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_bons_commande LIKE bons_commande;
ALTER TABLE archive_bons_commande ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_debourses LIKE debourses;
ALTER TABLE archive_debourses ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_planning_affaire LIKE planning_affaire;
ALTER TABLE archive_planning_affaire ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_fiches_expression_besoin LIKE fiches_expression_besoin;
ALTER TABLE archive_fiches_expression_besoin ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_decaissements LIKE decaissements;
ALTER TABLE archive_decaissements ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_factures_clients LIKE factures_clients;
ALTER TABLE archive_factures_clients ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
CREATE TABLE IF NOT EXISTS archive_encaissements LIKE encaissements;
ALTER TABLE archive_encaissements ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS archive_bl_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    archive_run_id INT NOT NULL,
    devis_id INT NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    signed_at VARCHAR(80) DEFAULT NULL,
    INDEX idx_archive_bl_run (archive_run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
