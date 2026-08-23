<?php

declare(strict_types=1);

namespace App\Domain\Quote;

use PDO;

final class QuoteRepository
{
    public function __construct(private readonly PDO $database) {}

    public function nextNumber(): string
    {
        $nextId = (int) $this->database->query('SELECT COALESCE(MAX(id), 0) + 1 FROM devis')->fetchColumn();
        return 'FI-DEV-PAB-' . $nextId;
    }

    public function create(array $quote): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO devis (numero_devis, delai_livraison, date_emission, date_expiration, date_facturation_prevue, emis_par, destine_a,
             termes_conditions, pied_de_page, total_ht, total_ttc, logo, client_id, offre_id, tva_facturable,
             publier_devis, tva, correspondant, created_by_user_id, masque, validation_commerciale, validation_generale)
             VALUES (:number, :delivery_time, :issued_at, :expires_at, :billing_at, :issuer, :recipient, :terms, :footer,
             :total_excluding_tax, :total_including_tax, :logo, :client_id, :offer_id, :taxable, :published, :tax, :contact,
             :created_by, 0, 0, 0)'
        );
        $statement->execute($quote);
        return (int) $this->database->lastInsertId();
    }

    public function addLine(int $quoteId, array $line): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO ligne_devis (devis_id, designation, prix, quantite, tva, remise, total)
             VALUES (:quote_id, :description, :price, :quantity, :tax, :discount, :total)'
        );
        $statement->execute(['quote_id' => $quoteId] + $line);
    }

    public function activity(): array
    {
        return $this->database->query(
            'SELECT DATE(date_emission) AS date, COUNT(*) AS count FROM devis
             WHERE masque = 0 GROUP BY DATE(date_emission) ORDER BY DATE(date_emission)'
        )->fetchAll();
    }
}
