<?php declare(strict_types=1); namespace App\Domain\Client; use App\Domain\Shared\Model;
final class Client extends Model { protected function primaryKey():string{return 'id_client';} public function name():string{return (string)$this->get('nom_client');} public function logo():?string{return $this->get('logo_client');} }
