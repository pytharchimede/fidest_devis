ALTER TABLE devis ADD COLUMN date_facturation_prevue DATE NULL AFTER date_expiration;
CREATE INDEX idx_devis_facturation_prevue ON devis(date_facturation_prevue);
CREATE TABLE notification_user (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,notification_key VARCHAR(190) NOT NULL,read_at DATETIME NULL,dismissed_at DATETIME NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uniq_user_notification(user_id,notification_key),INDEX idx_notification_user(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
