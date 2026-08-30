<?php declare(strict_types=1); namespace App\Domain\CashOut; use App\Domain\Shared\Repository;
final class CashOutRepository extends Repository { protected function table():string{return 'decaissements';} }
