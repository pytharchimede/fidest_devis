CREATE TABLE IF NOT EXISTS archive_client LIKE client;
ALTER TABLE archive_client ADD COLUMN IF NOT EXISTS archive_run_id_copy INT DEFAULT NULL;
