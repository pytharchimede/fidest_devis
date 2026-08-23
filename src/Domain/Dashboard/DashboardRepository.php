<?php
declare(strict_types=1);

namespace App\Domain\Dashboard;

use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $database) {}

    public function metrics(): array
    {
        $quote = $this->database->query(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(total_ttc), 0) revenue,
                    SUM(created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')) current_month,
                    SUM(validation_generale = 0 AND masque = 0) pending,
                    SUM(date_expiration < CURRENT_DATE AND validation_generale = 0 AND masque = 0) expired,
                    SUM((termes_conditions IS NULL OR TRIM(termes_conditions) = '') OR (pied_de_page IS NULL OR TRIM(pied_de_page) = '')) incomplete
             FROM devis WHERE masque = 0"
        )->fetch() ?: [];

        return $quote + [
            'clients' => (int) $this->database->query('SELECT COUNT(*) FROM client')->fetchColumn(),
            'offers' => (int) $this->database->query('SELECT COUNT(*) FROM offre')->fetchColumn(),
        ];
    }

    public function recentQuotes(int $limit = 5): array
    {
        $statement = $this->database->prepare(
            'SELECT id, numero_devis, destine_a, total_ttc, date_emission, date_expiration,
                    validation_commerciale, validation_generale
             FROM devis WHERE masque = 0 ORDER BY id DESC LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}
