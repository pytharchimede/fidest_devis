<?php

declare(strict_types=1);

namespace App\Application\Archive;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class CloseExercise
{
    public function __construct(private readonly PDO $db, private readonly string $root) {}
    public function execute(string $startDate, int $userId): int
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
        if (!$date || $date->format('Y-m-d') !== $startDate) throw new RuntimeException('La date de début d’exercice est invalide.');
        $previous = $this->db->query("SELECT setting_value FROM app_settings WHERE setting_key='exercise_start_date'")->fetchColumn() ?: null;
        if ($previous !== null && $startDate <= $previous) throw new RuntimeException('La nouvelle date doit être postérieure au début de l’exercice actuel.');
        $counts = $this->db->prepare('SELECT COUNT(*) offers,(SELECT COUNT(*) FROM devis d JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:quote_cutoff AND d.archived_at IS NULL) quotes FROM offre WHERE date_offre<:offer_cutoff AND archived_at IS NULL');
        $counts->execute(['quote_cutoff' => $startDate, 'offer_cutoff' => $startDate]);
        $counts = $counts->fetch();
        $this->db->beginTransaction();
        try {
            $run = $this->db->prepare('INSERT INTO archive_runs(exercise_start_date,previous_start_date,archived_offers,archived_quotes,created_by) VALUES(:start,:previous,:offers,:quotes,:user)');
            $run->execute(['start' => $startDate, 'previous' => $previous, 'offers' => (int)$counts['offers'], 'quotes' => (int)$counts['quotes'], 'user' => $userId]);
            $runId = (int)$this->db->lastInsertId();
            $queries = [
                'INSERT IGNORE INTO archive_client SELECT c.*,:run FROM client c WHERE EXISTS(SELECT 1 FROM offre o WHERE o.client_id=c.id_client AND o.date_offre<:cutoff AND o.archived_at IS NULL)',
                'INSERT IGNORE INTO archive_offre SELECT o.*,:run FROM offre o WHERE o.date_offre<:cutoff AND o.archived_at IS NULL',
                'INSERT IGNORE INTO archive_devis SELECT d.*,:run FROM devis d JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_ligne_devis SELECT l.*,:run FROM ligne_devis l JOIN devis d ON d.id=l.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_bons_commande SELECT bc.*,:run FROM bons_commande bc JOIN devis d ON d.id=bc.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_debourses SELECT x.*,:run FROM debourses x JOIN bons_commande bc ON bc.id=x.bon_commande_id JOIN devis d ON d.id=bc.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_planning_affaire SELECT x.*,:run FROM planning_affaire x JOIN bons_commande bc ON bc.id=x.bon_commande_id JOIN devis d ON d.id=bc.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_fiches_expression_besoin SELECT x.*,:run FROM fiches_expression_besoin x JOIN bons_commande bc ON bc.id=x.bon_commande_id JOIN devis d ON d.id=bc.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_decaissements SELECT x.*,:run FROM decaissements x JOIN fiches_expression_besoin f ON f.id=x.feb_id JOIN bons_commande bc ON bc.id=f.bon_commande_id JOIN devis d ON d.id=bc.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_factures_clients SELECT x.*,:run FROM factures_clients x JOIN bons_commande bc ON bc.id=x.bon_commande_id JOIN devis d ON d.id=bc.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
                'INSERT IGNORE INTO archive_encaissements SELECT x.*,:run FROM encaissements x JOIN factures_clients f ON f.id=x.facture_id JOIN bons_commande bc ON bc.id=f.bon_commande_id JOIN devis d ON d.id=bc.devis_id JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL',
            ];
            foreach ($queries as $sql) $this->db->prepare($sql)->execute(['run' => $runId, 'cutoff' => $startDate]);
            $quoteIds = $this->db->prepare('SELECT d.id FROM devis d JOIN offre o ON o.id_offre=d.offre_id WHERE o.date_offre<:cutoff AND d.archived_at IS NULL');
            $quoteIds->execute(['cutoff' => $startDate]);
            $quoteMap = array_flip(array_map('intval', $quoteIds->fetchAll(PDO::FETCH_COLUMN)));
            $registry = json_decode((string)@file_get_contents($this->root . '/data/bl_registry.json'), true);
            $bl = $this->db->prepare('INSERT INTO archive_bl_documents(archive_run_id,devis_id,fichier,signed_at) VALUES(:run,:quote,:file,:signed)');
            foreach (($registry['items'] ?? []) as $item) if (isset($quoteMap[(int)($item['devisId'] ?? 0)])) $bl->execute(['run' => $runId, 'quote' => (int)$item['devisId'], 'file' => (string)($item['file'] ?? ''), 'signed' => (string)($item['signedAt'] ?? '')]);
            $this->db->prepare('UPDATE devis d JOIN offre o ON o.id_offre=d.offre_id SET d.archived_at=NOW(),d.archive_run_id=:run WHERE o.date_offre<:cutoff AND d.archived_at IS NULL')->execute(['run' => $runId, 'cutoff' => $startDate]);
            $this->db->prepare('UPDATE offre SET archived_at=NOW(),archive_run_id=:run WHERE date_offre<:cutoff AND archived_at IS NULL')->execute(['run' => $runId, 'cutoff' => $startDate]);
            $this->db->prepare("INSERT INTO app_settings(setting_key,setting_value) VALUES('exercise_start_date',:start) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['start' => $startDate]);
            $this->db->commit();
            return $runId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
