<?php

declare(strict_types=1);

namespace App\Domain\PurchaseOrder;

use PDO;

final class PurchaseOrderRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function search(array $filters): array
    {
        $sql = 'SELECT bc.*,c.nom_client,d.numero_devis FROM bons_commande bc LEFT JOIN client c ON c.id_client=bc.client_id LEFT JOIN devis d ON d.id=bc.devis_id WHERE 1=1';
        $parameters = [];
        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $sql .= ' AND (bc.numero_bc LIKE :query OR bc.reference_client LIKE :query OR c.nom_client LIKE :query OR d.numero_devis LIKE :query OR bc.notes LIKE :query)';
            $parameters['query'] = '%' . $query . '%';
        }
        if (($filters['date_from'] ?? '') !== '') {
            $sql .= ' AND bc.date_reception >= :date_from';
            $parameters['date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $sql .= ' AND bc.date_reception <= :date_to';
            $parameters['date_to'] = $filters['date_to'];
        }
        if ((int) ($filters['client_id'] ?? 0) > 0) {
            $sql .= ' AND bc.client_id = :client_id';
            $parameters['client_id'] = (int) $filters['client_id'];
        }
        if ((int) ($filters['devis_id'] ?? 0) > 0) {
            $sql .= ' AND bc.devis_id = :devis_id';
            $parameters['devis_id'] = (int) $filters['devis_id'];
        }
        $status = (string) ($filters['statut'] ?? '');
        if (in_array($status, ['recu', 'traitement', 'execute', 'annule'], true)) {
            $sql .= ' AND bc.statut = :statut';
            $parameters['statut'] = $status;
        }
        $statement = $this->database->prepare($sql . ' ORDER BY bc.date_reception DESC,bc.id DESC');
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}
