<?php

declare(strict_types=1);

return static function (PDO $db): void {
    $tables = [
        'client' => 'archive_client',
        'offre' => 'archive_offre',
        'devis' => 'archive_devis',
        'ligne_devis' => 'archive_ligne_devis',
        'bons_commande' => 'archive_bons_commande',
        'debourses' => 'archive_debourses',
        'planning_affaire' => 'archive_planning_affaire',
        'fiches_expression_besoin' => 'archive_fiches_expression_besoin',
        'decaissements' => 'archive_decaissements',
        'factures_clients' => 'archive_factures_clients',
        'encaissements' => 'archive_encaissements',
    ];

    foreach ($tables as $source => $archive) {
        $sourceExists = (bool) $db->query(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name = " . $db->quote($source)
        )->fetchColumn();

        if (!$sourceExists) {
            continue;
        }

        $archiveExists = (bool) $db->query(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name = " . $db->quote($archive)
        )->fetchColumn();

        if (!$archiveExists) {
            $db->exec(
                "CREATE TABLE `{$archive}` LIKE `{$source}`"
            );
        }

        $hasRunColumn = (bool) $db->query(
            "SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
             AND table_name = " . $db->quote($archive) . "
             AND column_name = 'archive_run_id_copy'"
        )->fetchColumn();

        if (!$hasRunColumn) {
            $db->exec(
                "ALTER TABLE `{$archive}`
                 ADD COLUMN `archive_run_id_copy` INT NULL"
            );
        }
    }
};