<?php declare(strict_types=1); namespace App\Domain\Disbursement; use App\Domain\Shared\Repository;
final class DisbursementRepository extends Repository { protected function table():string{return 'debourses';} }
