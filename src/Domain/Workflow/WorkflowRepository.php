<?php
declare(strict_types=1);
namespace App\Domain\Workflow;
use PDO;
final class WorkflowRepository
{
    public function __construct(private readonly PDO $database){}
    public function refreshNeedRequests():void
    {
        $this->database->exec("INSERT IGNORE INTO fiches_expression_besoin(bon_commande_id,numero_feb,date_disponibilite,montant_demande,prepared_by,prepared_at,prepared_signature) SELECT d.bon_commande_id,CONCAT('FEB-',YEAR(MIN(p.date_debut)),'-',LPAD(d.bon_commande_id,5,'0')),DATE_SUB(MIN(p.date_debut),INTERVAL 7 DAY),d.montant_fournitures+d.montant_transport,d.created_by,d.valide_le,u.signature FROM debourses d JOIN planning_affaire p ON p.bon_commande_id=d.bon_commande_id LEFT JOIN user_devis u ON u.id=d.created_by WHERE d.statut='valide' GROUP BY d.bon_commande_id,d.montant_fournitures,d.montant_transport,d.created_by,d.valide_le,u.signature");
    }
    public function totals():array{return $this->database->query('SELECT (SELECT COALESCE(SUM(montant),0) FROM encaissements) entrees,(SELECT COALESCE(SUM(montant),0) FROM decaissements) sorties')->fetch()?:['entrees'=>0,'sorties'=>0];}
}
