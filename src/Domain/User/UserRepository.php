<?php
declare(strict_types=1);

namespace App\Domain\User;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $database) {}

    public function findActiveById(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, nom, prenom, mail_pro FROM user_devis WHERE id = :id AND active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }
}
