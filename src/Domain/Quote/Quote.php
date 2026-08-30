<?php declare(strict_types=1); namespace App\Domain\Quote; use App\Domain\Shared\Model;
final class Quote extends Model { protected function primaryKey():string{return 'id';} public function number():string{return (string)$this->get('numero_devis');} public function status():string{return (string)$this->get('statut_devis','en_attente');} }
