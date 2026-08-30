<?php declare(strict_types=1); namespace App\Domain\NeedRequest; use App\Domain\Shared\Model;
final class NeedRequest extends Model { protected function primaryKey():string{return 'id';} public function number():string{return (string)$this->get('numero_feb');} public function status():string{return (string)$this->get('statut');} }
