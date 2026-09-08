<?php

declare(strict_types=1);

namespace App\Application\Archive;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class CloseExercise
{
    public function __construct(
        private readonly PDO $db,
        private readonly string $root
    ) {
    }

    public function execute(string $startDate, int $userId): int
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);

        if (!$date || $date->format('Y-m-d') !== $startDate) {
            throw new RuntimeException(
                'La date de début d’exercice est invalide.'
            );
        }

        $previous = $this->db
            ->query(
                "SELECT setting_value
                 FROM app_settings
                 WHERE setting_key='exercise_start_date'"
            )
            ->fetchColumn() ?: null;

        if ($previous !== null && $startDate <= $previous) {
            throw new RuntimeException(
                'La nouvelle date doit être postérieure au début de l’exercice actuel.'
            );
        }

        // Les copies d'archives réutilisent cette borne dans archiveTable().
        // Sans cette affectation, le paramètre :cutoff est NULL et aucune ligne
        // n'est copiée alors que les documents source sont ensuite masqués.
        $this->currentCutoff = $startDate;

        $counts = $this->db->prepare(
            'SELECT
                COUNT(*) AS offers,
                (
                    SELECT COUNT(*)
                    FROM devis d
                    JOIN offre o ON o.id_offre = d.offre_id
                    WHERE o.date_offre < :quote_cutoff
                    AND d.archived_at IS NULL
                ) AS quotes
             FROM offre
             WHERE date_offre < :offer_cutoff
             AND archived_at IS NULL'
        );

        $counts->execute([
            'quote_cutoff' => $startDate,
            'offer_cutoff' => $startDate,
        ]);

        $counts = $counts->fetch(PDO::FETCH_ASSOC);

        if (!$counts) {
            $counts = [
                'offers' => 0,
                'quotes' => 0,
            ];
        }

        $this->db->beginTransaction();

        try {
            $run = $this->db->prepare(
                'INSERT INTO archive_runs
                (
                    exercise_start_date,
                    previous_start_date,
                    archived_offers,
                    archived_quotes,
                    created_by
                )
                VALUES
                (
                    :start,
                    :previous,
                    :offers,
                    :quotes,
                    :user
                )'
            );

            $run->execute([
                'start' => $startDate,
                'previous' => $previous,
                'offers' => (int) $counts['offers'],
                'quotes' => (int) $counts['quotes'],
                'user' => $userId,
            ]);

            $runId = (int) $this->db->lastInsertId();

            /*
             * Copie sécurisée des tables d'archives.
             *
             * IMPORTANT :
             * On ne fait plus :
             *
             *     INSERT ... SELECT source.*,:run
             *
             * car le nombre de colonnes peut différer.
             */
            $this->archiveTable(
                'client',
                'archive_client',
                $runId,
                "
                WHERE EXISTS (
                    SELECT 1
                    FROM offre o
                    WHERE o.client_id = client.id_client
                    AND o.date_offre < :cutoff
                    AND o.archived_at IS NULL
                )
                "
            );

            $this->archiveTable(
                'offre',
                'archive_offre',
                $runId,
                "
                WHERE offre.date_offre < :cutoff
                AND offre.archived_at IS NULL
                "
            );

            $this->archiveTable(
                'devis',
                'archive_devis',
                $runId,
                "
                INNER JOIN offre o
                    ON o.id_offre = devis.offre_id
                WHERE o.date_offre < :cutoff
                AND devis.archived_at IS NULL
                "
            );

            $this->archiveTable(
                'ligne_devis',
                'archive_ligne_devis',
                $runId,
                "
                INNER JOIN devis d
                    ON d.id = ligne_devis.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                AND d.archived_at IS NULL
                "
            );

            $this->archiveTable(
                'bons_commande',
                'archive_bons_commande',
                $runId,
                "
                INNER JOIN devis d
                    ON d.id = bons_commande.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                AND d.archived_at IS NULL
                "
            );

            $this->archiveTable(
                'debourses',
                'archive_debourses',
                $runId,
                "
                INNER JOIN bons_commande bc
                    ON bc.id = debourses.bon_commande_id
                INNER JOIN devis d
                    ON d.id = bc.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                AND d.archived_at IS NULL
                "
            );

            $this->archiveTable(
                'planning_affaire',
                'archive_planning_affaire',
                $runId,
                "
                INNER JOIN bons_commande bc
                    ON bc.id = planning_affaire.bon_commande_id
                INNER JOIN devis d
                    ON d.id = bc.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                "
            );

            $this->archiveTable(
                'fiches_expression_besoin',
                'archive_fiches_expression_besoin',
                $runId,
                "
                INNER JOIN bons_commande bc
                    ON bc.id = fiches_expression_besoin.bon_commande_id
                INNER JOIN devis d
                    ON d.id = bc.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                "
            );

            $this->archiveTable(
                'decaissements',
                'archive_decaissements',
                $runId,
                "
                INNER JOIN fiches_expression_besoin f
                    ON f.id = decaissements.feb_id
                INNER JOIN bons_commande bc
                    ON bc.id = f.bon_commande_id
                INNER JOIN devis d
                    ON d.id = bc.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                "
            );

            $this->archiveTable(
                'factures_clients',
                'archive_factures_clients',
                $runId,
                "
                INNER JOIN bons_commande bc
                    ON bc.id = factures_clients.bon_commande_id
                INNER JOIN devis d
                    ON d.id = bc.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                "
            );

            $this->archiveTable(
                'encaissements',
                'archive_encaissements',
                $runId,
                "
                INNER JOIN factures_clients f
                    ON f.id = encaissements.facture_id
                INNER JOIN bons_commande bc
                    ON bc.id = f.bon_commande_id
                INNER JOIN devis d
                    ON d.id = bc.devis_id
                INNER JOIN offre o
                    ON o.id_offre = d.offre_id
                WHERE o.date_offre < :cutoff
                "
            );

            /*
             * Documents BL.
             */
            $quoteIds = $this->db->prepare(
                'SELECT d.id
                 FROM devis d
                 JOIN offre o ON o.id_offre = d.offre_id
                 WHERE o.date_offre < :cutoff
                 AND d.archived_at IS NULL'
            );

            $quoteIds->execute([
                'cutoff' => $startDate,
            ]);

            $quoteMap = array_flip(
                array_map(
                    'intval',
                    $quoteIds->fetchAll(PDO::FETCH_COLUMN)
                )
            );

            $registryPath = $this->root . '/data/bl_registry.json';

            $registry = [];

            if (is_file($registryPath)) {
                $registry = json_decode(
                    (string) file_get_contents($registryPath),
                    true
                );

                if (!is_array($registry)) {
                    $registry = [];
                }
            }

            $bl = $this->db->prepare(
                'INSERT INTO archive_bl_documents
                (
                    archive_run_id,
                    devis_id,
                    fichier,
                    signed_at
                )
                VALUES
                (
                    :run,
                    :quote,
                    :file,
                    :signed
                )'
            );

            foreach (($registry['items'] ?? []) as $item) {
                $devisId = (int) ($item['devisId'] ?? 0);

                if (!isset($quoteMap[$devisId])) {
                    continue;
                }

                $bl->execute([
                    'run' => $runId,
                    'quote' => $devisId,
                    'file' => (string) ($item['file'] ?? ''),
                    'signed' => (string) ($item['signedAt'] ?? ''),
                ]);
            }

            /*
             * Marquage des devis archivés.
             */
            $this->db->prepare(
                'UPDATE devis d
                 JOIN offre o ON o.id_offre = d.offre_id
                 SET
                    d.archived_at = NOW(),
                    d.archive_run_id = :run
                 WHERE o.date_offre < :cutoff
                 AND d.archived_at IS NULL'
            )->execute([
                'run' => $runId,
                'cutoff' => $startDate,
            ]);

            /*
             * Marquage des appels d'offres archivés.
             */
            $this->db->prepare(
                'UPDATE offre
                 SET
                    archived_at = NOW(),
                    archive_run_id = :run
                 WHERE date_offre < :cutoff
                 AND archived_at IS NULL'
            )->execute([
                'run' => $runId,
                'cutoff' => $startDate,
            ]);

            /*
             * Nouvelle date d'exercice.
             */
            $this->db->prepare(
                "INSERT INTO app_settings
                (
                    setting_key,
                    setting_value
                )
                VALUES
                (
                    'exercise_start_date',
                    :start
                )
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value)"
            )->execute([
                'start' => $startDate,
            ]);

            $this->db->commit();

            return $runId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Copie uniquement les colonnes communes entre la table source
     * et la table d'archive.
     */
    private function archiveTable(
        string $source,
        string $archive,
        int $runId,
        string $joinsAndWhere
    ): void {
        $sourceColumns = $this->getColumns($source);
        $archiveColumns = $this->getColumns($archive);

        if (!$sourceColumns || !$archiveColumns) {
            throw new RuntimeException(
                "Table d'archive introuvable : {$archive}"
            );
        }

        /*
         * On conserve uniquement les colonnes présentes dans les deux
         * tables, sauf archive_run_id_copy qui est ajouté séparément.
         */
        $columns = array_values(
            array_intersect($sourceColumns, $archiveColumns)
        );

        $columns = array_values(
            array_filter(
                $columns,
                static fn(string $column): bool =>
                    $column !== 'archive_run_id_copy'
            )
        );

        if (!$columns) {
            throw new RuntimeException(
                "Aucune colonne commune entre {$source} et {$archive}."
            );
        }

        $quotedColumns = implode(
            ', ',
            array_map(
                static fn(string $column): string =>
                    '`' . str_replace('`', '``', $column) . '`',
                $columns
            )
        );

        $selectColumns = implode(
            ', ',
            array_map(
                static fn(string $column): string =>
                    $source . '.`' .
                    str_replace('`', '``', $column) .
                    '`',
                $columns
            )
        );

        $sql = "
            INSERT IGNORE INTO `{$archive}`
            ({$quotedColumns}, `archive_run_id_copy`)
            SELECT
                {$selectColumns},
                :run
            FROM `{$source}`
            {$joinsAndWhere}
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'run' => $runId,
            'cutoff' => $this->currentCutoff,
        ]);
    }

    private ?string $currentCutoff = null;

    private function getColumns(string $table): array
    {
        $statement = $this->db->query(
            'SHOW COLUMNS FROM `' .
            str_replace('`', '``', $table) .
            '`'
        );

        return array_values(
            array_map(
                static fn(array $row): string => (string) $row['Field'],
                $statement->fetchAll(PDO::FETCH_ASSOC)
            )
        );
    }
}
