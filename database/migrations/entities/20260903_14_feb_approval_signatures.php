<?php declare(strict_types=1); return static function(PDO $db):void{
    $db->exec("ALTER TABLE user_devis ADD COLUMN IF NOT EXISTS signature_mime VARCHAR(60) NULL AFTER signature");
    $db->exec("ALTER TABLE user_devis ADD COLUMN IF NOT EXISTS signature_updated_at DATETIME NULL AFTER signature_mime");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS prepared_by INT NULL AFTER motif_rejet");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS prepared_at DATETIME NULL AFTER prepared_by");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS prepared_signature VARCHAR(255) NULL AFTER prepared_at");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS analyzed_by INT NULL AFTER prepared_signature");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS analyzed_at DATETIME NULL AFTER analyzed_by");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS analyzed_signature VARCHAR(255) NULL AFTER analyzed_at");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER analyzed_signature");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by");
    $db->exec("ALTER TABLE fiches_expression_besoin ADD COLUMN IF NOT EXISTS approved_signature VARCHAR(255) NULL AFTER approved_at");
    $db->exec("UPDATE fiches_expression_besoin feb JOIN debourses db ON db.bon_commande_id=feb.bon_commande_id LEFT JOIN user_devis u ON u.id=db.created_by SET feb.prepared_by=COALESCE(feb.prepared_by,db.created_by),feb.prepared_at=COALESCE(feb.prepared_at,db.valide_le),feb.prepared_signature=COALESCE(feb.prepared_signature,u.signature)");
};
