<?php declare(strict_types=1); namespace App\Domain\PurchaseOrder; use App\Domain\Shared\Model;
final class PurchaseOrder extends Model { protected function primaryKey():string{return 'id';} public function number():string{return (string)$this->get('numero_bc');} }
