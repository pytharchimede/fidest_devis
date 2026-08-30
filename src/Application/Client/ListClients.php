<?php declare(strict_types=1); namespace App\Application\Client; use App\Domain\Client\Client; use App\Domain\Client\ClientRepository;
final class ListClients { public function __construct(private readonly ClientRepository $repository){} public function execute():array{return array_map(static fn(array $row)=>new Client($row),$this->repository->all());} }
