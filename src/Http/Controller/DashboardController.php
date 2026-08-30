<?php
declare(strict_types=1);
namespace App\Http\Controller;
use App\Application\Analytics\GetDashboardData;
final class DashboardController
{
    public function __construct(private readonly GetDashboardData $dashboardData){}
    public function index(array $query=[]):array{return $this->dashboardData->execute($query);}
}
