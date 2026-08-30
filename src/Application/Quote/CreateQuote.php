<?php
declare(strict_types=1);

namespace App\Application\Quote;

use App\Domain\Quote\QuoteRepository;
use PDO;
use Throwable;

final class CreateQuote
{
    public function __construct(
        private readonly PDO $database,
        private readonly QuoteRepository $quotes,
    ) {}

    public function execute(array $quote, array $lines): int
    {
        $this->database->beginTransaction();
        try {
            $reservation = $this->database->prepare('INSERT INTO quote_offer_reservations (offer_id) VALUES (:offer_id)');
            $reservation->execute(['offer_id' => (int) $quote['offer_id']]);
            $quote['number'] = $this->quotes->nextNumber();
            $quoteId = $this->quotes->create($quote);
            $this->database->prepare('UPDATE quote_offer_reservations SET quote_id=:quote_id WHERE offer_id=:offer_id')
                ->execute(['quote_id'=>$quoteId,'offer_id'=>(int)$quote['offer_id']]);
            foreach ($lines as $line) {
                $this->quotes->addLine($quoteId, $line);
            }
            $this->database->commit();
            return $quoteId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }
}
