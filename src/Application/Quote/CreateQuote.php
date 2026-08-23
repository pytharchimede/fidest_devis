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
            $quote['number'] = $this->quotes->nextNumber();
            $quoteId = $this->quotes->create($quote);
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
