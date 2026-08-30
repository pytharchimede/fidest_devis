<?php declare(strict_types=1); namespace App\Domain\Planning; use App\Domain\Shared\Model;
final class Planning extends Model { protected function primaryKey():string{return 'id';} public function startsAt():string{return (string)$this->get('date_debut');} }
