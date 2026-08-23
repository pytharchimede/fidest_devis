<?php
declare(strict_types=1);

namespace App\Domain\Offer;

use PDO;

final class OfferRepository
{
    public function __construct(private readonly PDO $database) {}

    public function all(): array
    {
        return $this->database->query('SELECT * FROM offre ORDER BY date_offre DESC, id_offre DESC')->fetchAll();
    }

    public function create(array $offer): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO offre (num_offre, date_offre, reference_offre, commercial_dedie, date_creat_offre)
             VALUES (:number, :offer_date, :reference, :sales_contact, :created_at)'
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
        $statement = $this->database->prepare('UPDATE offre SET num_offre=:number, date_offre=:offer_date,
            reference_offre=:reference, commercial_dedie=:sales_contact,
            date_creat_offre=:created_at WHERE id_offre=:id');
        $statement->execute($offer + ['id' => $id]);
    }
}
