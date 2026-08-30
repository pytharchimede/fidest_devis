<?php declare(strict_types=1); namespace App\Domain\Planning; use App\Domain\Shared\Repository;
final class PlanningRepository extends Repository { protected function table():string{return 'planning_affaire';} }
