<?php declare(strict_types=1); namespace App\Domain\Delivery; use App\Domain\Shared\Model;
final class Delivery extends Model { protected function primaryKey():string{return 'id';} public function signedAt():string{return (string)$this->get('date_signature');} }
