<?php
declare(strict_types=1);
namespace App\Domain\Billing;
use PDO;

final class BillingRepository
{
    public function __construct(private readonly PDO $database) {}
    public function metrics(): array
    {
        return $this->database->query("SELECT COUNT(*) total,
            SUM(date_facturation_prevue IS NULL) unscheduled,
            SUM(date_facturation_prevue BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL 7 DAY)) upcoming,
            SUM(date_facturation_prevue < CURRENT_DATE) overdue,
            COALESCE(SUM(CASE WHEN date_facturation_prevue IS NOT NULL THEN total_ttc ELSE 0 END),0) scheduled_amount
            FROM devis WHERE masque=0")->fetch() ?: [];
    }
    public function search(array $filters): array
    {
        $where=['d.masque=0'];$params=[];
        $q=trim((string)($filters['q']??''));if($q!==''){$where[]='(d.numero_devis LIKE :q_number OR c.nom_client LIKE :q_client OR d.destine_a LIKE :q_recipient)';$params['q_number']=$params['q_client']=$params['q_recipient']='%'.$q.'%';}
        $status=(string)($filters['status']??'');
        if($status==='unscheduled')$where[]='d.date_facturation_prevue IS NULL';
        elseif($status==='upcoming')$where[]='d.date_facturation_prevue BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL 7 DAY)';
        elseif($status==='overdue')$where[]='d.date_facturation_prevue < CURRENT_DATE';
        elseif($status==='scheduled')$where[]='d.date_facturation_prevue IS NOT NULL';
        $statement=$this->database->prepare('SELECT d.id,d.numero_devis,d.destine_a,d.total_ht,d.total_ttc,d.date_emission,d.date_facturation_prevue,d.validation_generale,c.nom_client FROM devis d LEFT JOIN client c ON c.id_client=d.client_id WHERE '.implode(' AND ',$where).' ORDER BY d.date_facturation_prevue IS NULL,d.date_facturation_prevue,d.id DESC LIMIT 250');
        $statement->execute($params);return $statement->fetchAll();
    }
    public function schedule(int $quoteId, ?string $date): void
    {
        $statement=$this->database->prepare('UPDATE devis SET date_facturation_prevue=:date WHERE id=:id');
        $statement->execute(['date'=>$date ?: null,'id'=>$quoteId]);
    }
}
