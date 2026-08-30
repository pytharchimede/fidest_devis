<?php declare(strict_types=1); namespace App\Domain\Payment; use App\Domain\Shared\Model;
final class Payment extends Model { protected function primaryKey():string{return 'id';} public function amount():float{return (float)$this->get('montant');} }
