<?php declare(strict_types=1); namespace App\Domain\Invoice; use App\Domain\Shared\Repository;
final class InvoiceRepository extends Repository { protected function table():string{return 'factures_clients';} }
