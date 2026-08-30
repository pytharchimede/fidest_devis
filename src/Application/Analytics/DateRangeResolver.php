<?php
declare(strict_types=1);
namespace App\Application\Analytics;
use DateTimeImmutable;
final class DateRangeResolver
{
    public function resolve(array $input):array
    {
        $period=(string)($input['analytics_period']??'month');$today=new DateTimeImmutable('today');
        [$start,$end]=match($period){
            'day'=>[$today,$today],
            'week'=>[$today->modify('monday this week'),$today->modify('sunday this week')],
            'quarter'=>[$today->setDate((int)$today->format('Y'),((int)ceil((int)$today->format('n')/3)-1)*3+1,1),null],
            'semester'=>[$today->setDate((int)$today->format('Y'),(int)$today->format('n')<=6?1:7,1),null],
            'year'=>[$today->setDate((int)$today->format('Y'),1,1),$today->setDate((int)$today->format('Y'),12,31)],
            'custom'=>$this->custom($input,$today),
            default=>[$today->modify('first day of this month'),$today->modify('last day of this month')],
        };
        if($end===null)$end=$period==='quarter'?$start->modify('+2 months')->modify('last day of this month'):$start->modify('+5 months')->modify('last day of this month');
        return ['period'=>$period,'start'=>$start->format('Y-m-d'),'end'=>$end->format('Y-m-d'),'label'=>$start->format('d/m/Y').' — '.$end->format('d/m/Y')];
    }
    private function custom(array $input,DateTimeImmutable $today):array
    {
        $start=DateTimeImmutable::createFromFormat('!Y-m-d',(string)($input['analytics_start']??''))?:$today->modify('first day of this month');$end=DateTimeImmutable::createFromFormat('!Y-m-d',(string)($input['analytics_end']??''))?:$today;if($start>$end)[$start,$end]=[$end,$start];return[$start,$end];
    }
}
