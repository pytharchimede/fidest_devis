CREATE TABLE IF NOT EXISTS app_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    url VARCHAR(255) DEFAULT NULL,
    starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ends_at DATETIME DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_announcements_window (active, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO app_announcements (title, message, url, starts_at, active)
SELECT 'FIDEST fait peau neuve',
       'Le logiciel évolue avec une interface modernisée, une facturation désormais active et un centre de notifications pour mieux suivre vos échéances. Découvrez les nouveaux guides disponibles sur chaque page.',
       'dashboard.php', CURRENT_TIMESTAMP, 1
WHERE NOT EXISTS (SELECT 1 FROM app_announcements WHERE title = 'FIDEST fait peau neuve');
