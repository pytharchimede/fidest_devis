<?php declare(strict_types=1); namespace App\Domain\Delivery; use App\Domain\Shared\Repository;
final class DeliveryRepository extends Repository { protected function table():string{return 'delivery_documents';} }
