<?php declare(strict_types=1); namespace App\Application\NeedRequest; use App\Domain\NeedRequest\NeedRequestRepository;
final class GetNeedRequestRegistry { public function __construct(private readonly NeedRequestRepository $repository){} public function execute():array{return $this->repository->registry();} }
