<?php
declare(strict_types=1);
namespace App\Application\Analytics;
use App\Domain\Analytics\AnalyticsRepository;use App\Domain\Dashboard\DashboardRepository;
final class GetDashboardData
{
    public function __construct(private readonly DashboardRepository $dashboard,private readonly AnalyticsRepository $analytics,private readonly string $blRegistry){}
    public function execute(array $filters=[]):array
    {
        $registry=json_decode((string)@file_get_contents($this->blRegistry),true);$items=$registry['items']??[];
        $range=(new DateRangeResolver())->resolve($filters);
        return ['metrics'=>$this->dashboard->metrics(),'recentQuotes'=>$this->dashboard->recentQuotes(),'analytics'=>$this->analytics->summary($items,$range['start'],$range['end']),'monthlyAnalytics'=>$this->analytics->monthly($items,$range['start'],$range['end']),'analyticsRange'=>$range];
    }
}
