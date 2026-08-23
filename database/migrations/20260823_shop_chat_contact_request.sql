ALTER TABLE conversations_boutique ADD COLUMN IF NOT EXISTS telephone_visiteur VARCHAR(40) DEFAULT NULL AFTER email_visiteur;
ALTER TABLE conversations_boutique ADD COLUMN IF NOT EXISTS whatsapp_visiteur VARCHAR(40) DEFAULT NULL AFTER telephone_visiteur;
ALTER TABLE conversations_boutique ADD COLUMN IF NOT EXISTS contact_request VARCHAR(20) DEFAULT NULL AFTER whatsapp_visiteur;
ALTER TABLE conversations_boutique ADD COLUMN IF NOT EXISTS contact_requested_at DATETIME DEFAULT NULL AFTER contact_request;
