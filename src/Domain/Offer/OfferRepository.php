<?php
declare(strict_types=1);

namespace App\Domain\Offer;

use PDO;

final class OfferRepository
{
    public function __construct(private readonly PDO $database) {}

    public function all(): array
    {
        return $this->database->query('SELECT o.*,c.nom_client,c.logo_client,r.quote_id AS reserved_quote_id,d.total_ttc AS montant_devis,COALESCE(d.total_ttc,o.montant_estime) AS montant_affiche FROM offre o LEFT JOIN client c ON c.id_client=o.client_id LEFT JOIN quote_offer_reservations r ON r.offer_id=o.id_offre LEFT JOIN devis d ON d.id=r.quote_id WHERE o.archived_at IS NULL ORDER BY o.date_offre DESC,o.id_offre DESC')->fetchAll();
    }

    public function create(array $offer): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO offre (client_id,num_offre,date_offre,date_limite_reponse,reference_offre,titre_offre,montant_estime,commercial_dedie,date_creat_offre,statut,motif_perte,fichier_ao,fichier_ao_original)
             VALUES (:client_id,:number,:offer_date,:response_deadline,:reference,:title,:estimated_amount,:sales_contact,:created_at,:status,:loss_reason,:file_path,:original_name)'
        );
        $statement->execute($offer);

        return (int) $this->database->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM offre WHERE id_offre = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function update(int $id, array $offer): void
    {
        $statement = $this->database->prepare('UPDATE offre SET client_id=:client_id,num_offre=:number,date_offre=:offer_date,date_limite_reponse=:response_deadline,
            reference_offre=:reference,titre_offre=:title,montant_estime=:estimated_amount,commercial_dedie=:sales_contact,
            date_creat_offre=:created_at,statut=:status,motif_perte=:loss_reason WHERE id_offre=:id');
        $statement->execute($offer + ['id' => $id]);
    }
}
