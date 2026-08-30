<?php declare(strict_types=1); namespace App\Domain\Disbursement; use App\Domain\Shared\Model;
final class Disbursement extends Model { protected function primaryKey():string{return 'id';} public function total():float{return (float)$this->get('montant_fournitures')+(float)$this->get('montant_transport');} }
