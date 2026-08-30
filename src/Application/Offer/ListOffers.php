<?php declare(strict_types=1); namespace App\Application\Offer; use App\Domain\Offer\Offer; use App\Domain\Offer\OfferRepository;
final class ListOffers { public function __construct(private readonly OfferRepository $repository){} public function execute():array{return array_map(static fn(array $row)=>new Offer($row),$this->repository->all());} }
