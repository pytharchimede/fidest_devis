<?php declare(strict_types=1); namespace App\Domain\Payment; use App\Domain\Shared\Repository;
final class PaymentRepository extends Repository { protected function table():string{return 'encaissements';} }
