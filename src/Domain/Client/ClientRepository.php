<?php
declare(strict_types=1);

namespace App\Domain\Client;

use PDO;

final class ClientRepository
{
    public function __construct(private readonly PDO $database) {}

    public function all(): array
    {
        return $this->database->query('SELECT * FROM client ORDER BY nom_client')->fetchAll();
    }

    public function create(array $client): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO client (code_client, nom_client, localisation_client, commune_client, bp_client, pays_client, date_creat_client)
             VALUES (:code, :name, :location, :city, :postal_box, :country, :created_at)'
        );
        $statement->execute($client);

        return (int) $this->database->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM client WHERE id_client = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function update(int $id, array $client): void
    {
        $statement = $this->database->prepare('UPDATE client SET code_client=:code, nom_client=:name,
            localisation_client=:location, commune_client=:city, bp_client=:postal_box,
            pays_client=:country, date_creat_client=:created_at WHERE id_client=:id');
        $statement->execute($client + ['id' => $id]);
    }
}
