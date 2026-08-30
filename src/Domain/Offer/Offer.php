<?php declare(strict_types=1); namespace App\Domain\Offer; use App\Domain\Shared\Model;
final class Offer extends Model { protected function primaryKey():string{return 'id_offre';} public function title():string{return (string)($this->get('titre_offre')?:$this->get('num_offre'));} public function status():string{return (string)$this->get('statut','en_cours');} }
