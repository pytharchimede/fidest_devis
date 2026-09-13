<?php
declare(strict_types=1);

return static function (PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS treasury_settings (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
        opening_date DATE NOT NULL,
        updated_by INT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("INSERT IGNORE INTO treasury_settings (id,opening_balance,opening_date) VALUES (1,0,CURRENT_DATE)");

    $db->exec("CREATE TABLE IF NOT EXISTS feb_funding_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feb_id INT NOT NULL,
        source_type VARCHAR(30) NOT NULL,
        invoice_id INT NULL,
        amount DECIMAL(15,2) NOT NULL,
        scheduled_date DATE NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'planned',
        note TEXT NULL,
        planned_by INT NOT NULL,
        planned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_active_feb_plan (feb_id),
        INDEX idx_funding_schedule (scheduled_date,status),
        INDEX idx_funding_invoice (invoice_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS daily_disbursement_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        point_number VARCHAR(60) NOT NULL,
        point_date DATE NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'closed',
        total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
        closed_by INT NOT NULL,
        closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        dg_signed_by INT NULL,
        dg_signed_at DATETIME NULL,
        dg_signature VARCHAR(255) NULL,
        executed_by INT NULL,
        executed_at DATETIME NULL,
        document_uid VARCHAR(80) NOT NULL,
        document_version INT NOT NULL DEFAULT 1,
        UNIQUE KEY uniq_daily_point_date (point_date),
        UNIQUE KEY uniq_daily_point_number (point_number),
        UNIQUE KEY uniq_daily_point_uid (document_uid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS daily_disbursement_point_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        point_id INT NOT NULL,
        funding_plan_id INT NOT NULL,
        feb_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        scheduled_date DATE NOT NULL,
        source_type VARCHAR(30) NOT NULL,
        invoice_id INT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'closed',
        payment_reference VARCHAR(150) NULL,
        disbursement_id INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_point_plan (point_id,funding_plan_id),
        INDEX idx_point_line_feb (feb_id),
        INDEX idx_point_line_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("ALTER TABLE decaissements ADD COLUMN IF NOT EXISTS point_line_id INT NULL AFTER feb_id");
    $db->exec("ALTER TABLE decaissements ADD UNIQUE KEY IF NOT EXISTS uniq_cash_out_point_line (point_line_id)");
};
