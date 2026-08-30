<?php declare(strict_types=1); namespace App\Domain\Invoice; use App\Domain\Shared\Model;
final class Invoice extends Model { protected function primaryKey():string{return 'id';} public function number():string{return (string)$this->get('numero_facture');} public function amount():float{return (float)$this->get('montant_ttc');} }
