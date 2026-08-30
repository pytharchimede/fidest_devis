<?php
declare(strict_types=1);

namespace App\Domain\Quote;

use PDO;

final class QuoteSearchRepository
{
    public function __construct(private readonly PDO $db) {}

    public function options(): array
    {
        return [
            'clients' => $this->db->query('SELECT id_client AS id, nom_client AS label FROM client ORDER BY nom_client')->fetchAll(),
            'users' => $this->db->query("SELECT id, TRIM(CONCAT(prenom, ' ', nom)) AS label FROM user_devis WHERE active = 1 ORDER BY prenom, nom")->fetchAll(),
            'products' => $this->db->query("SELECT DISTINCT TRIM(designation) AS id, TRIM(designation) AS label FROM ligne_devis WHERE TRIM(designation) <> '' ORDER BY label")->fetchAll(),
        ];
    }

    public function search(array $filters): array
    {
        $where = ['d.masque = 0', 'd.archived_at IS NULL'];
        $params = [];
        $this->addInFilter($where, $params, 'd.client_id', (array) ($filters['clients'] ?? []), 'client');

        $userIds = array_values(array_filter(array_map('intval', (array) ($filters['users'] ?? []))));
        if ($userIds !== []) {
            $fkMarks = $legacyMarks = [];
            foreach ($userIds as $index => $userId) {
                $fkKey = 'editor_fk_' . $index;
                $legacyKey = 'editor_legacy_' . $index;
                $fkMarks[] = ':' . $fkKey;
                $legacyMarks[] = ':' . $legacyKey;
                $params[$fkKey] = $userId;
                $params[$legacyKey] = $userId;
            }
            $where[] = '(d.created_by_user_id IN (' . implode(',', $fkMarks) . ') OR EXISTS (
                SELECT 1 FROM user_devis ux WHERE ux.id IN (' . implode(',', $legacyMarks) . ')
                AND CONVERT(d.emis_par USING utf8mb3) COLLATE utf8mb3_general_ci
                    LIKE CONCAT("%", CONVERT(ux.nom USING utf8mb3) COLLATE utf8mb3_general_ci, "%")
            ))';
        }

        $products = array_values(array_filter((array) ($filters['products'] ?? []), 'strlen'));
        if ($products !== []) {
            $marks = [];
            foreach ($products as $index => $product) {
                $key = 'product_' . $index;
                $marks[] = ':' . $key;
                $params[$key] = $product;
            }
            $where[] = 'EXISTS (SELECT 1 FROM ligne_devis ld WHERE ld.devis_id = d.id
                AND TRIM(ld.designation) IN (' . implode(',', $marks) . '))';
        }

        foreach ([
            'date_debut' => ['d.date_emission >=', 'date_start'],
            'date_fin' => ['d.date_emission <=', 'date_end'],
            'montant_min' => ['d.total_ttc >=', 'amount_min'],
            'montant_max' => ['d.total_ttc <=', 'amount_max'],
        ] as $filter => [$operator, $key]) {
            if (($filters[$filter] ?? '') !== '') {
                $where[] = $operator . ' :' . $key;
                $params[$key] = $filters[$filter];
            }
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'pending') $where[] = 'd.validation_generale = 0';
        elseif ($status === 'commercial') $where[] = 'd.validation_commerciale = 0';
        elseif ($status === 'general') $where[] = 'd.validation_commerciale = 1 AND d.validation_generale = 0';
        elseif ($status === 'validated') $where[] = 'd.validation_commerciale = 1 AND d.validation_generale = 1';
        elseif ($status === 'expired') $where[] = 'd.date_expiration < CURRENT_DATE AND d.validation_generale = 0';
        elseif ($status === 'incomplete') $where[] = "((d.termes_conditions IS NULL OR TRIM(d.termes_conditions) = '') OR (d.pied_de_page IS NULL OR TRIM(d.pied_de_page) = ''))";

        if (($filters['period'] ?? '') === 'current_month') {
            $where[] = "d.created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')";
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $where[] = '(d.numero_devis LIKE :query OR d.destine_a LIKE :query
                OR d.correspondant LIKE :query OR c.nom_client LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $statement = $this->db->prepare('SELECT d.*, c.nom_client FROM devis d
            LEFT JOIN client c ON c.id_client = d.client_id
            WHERE ' . implode(' AND ', $where) . ' ORDER BY d.id DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    private function addInFilter(array &$where, array &$params, string $column, array $values, string $prefix): void
    {
        $values = array_values(array_filter($values, static fn ($value): bool => $value !== ''));
        if ($values === []) return;
        $marks = [];
        foreach ($values as $index => $value) {
            $key = $prefix . '_' . $index;
            $marks[] = ':' . $key;
            $params[$key] = $value;
        }
        $where[] = $column . ' IN (' . implode(',', $marks) . ')';
    }
}
