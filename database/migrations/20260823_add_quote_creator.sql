ALTER TABLE devis
    ADD COLUMN IF NOT EXISTS created_by_user_id INT NULL AFTER correspondant;
