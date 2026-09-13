<?php
declare(strict_types=1);

namespace App\Domain\Treasury;

use App\Domain\Shared\Repository;

final class TreasuryRepository extends Repository
{
    protected function table(): string { return 'treasury_settings'; }

    public function dashboard(): array
    {
        $settings=$this->database->query('SELECT * FROM treasury_settings WHERE id=1')->fetch()?:['opening_balance'=>0,'opening_date'=>date('Y-m-d')];
        $realIn=(float)$this->database->query("SELECT COALESCE(SUM(montant),0) FROM encaissements WHERE date_encaissement >= ".$this->database->quote((string)$settings['opening_date']))->fetchColumn();
        $realOut=(float)$this->database->query("SELECT COALESCE(SUM(montant),0) FROM decaissements WHERE date_decaissement >= ".$this->database->quote((string)$settings['opening_date']))->fetchColumn();
        $forecastIn=(float)$this->database->query("SELECT COALESCE(SUM(GREATEST(f.montant_ttc-COALESCE(p.paid,0),0)),0) FROM factures_clients f LEFT JOIN (SELECT facture_id,SUM(montant) paid FROM encaissements GROUP BY facture_id) p ON p.facture_id=f.id WHERE f.statut<>'annulee'")->fetchColumn();
        $planned=(float)$this->database->query("SELECT COALESCE(SUM(amount),0) FROM feb_funding_plans WHERE status='planned'")->fetchColumn();
        $real=(float)$settings['opening_balance']+$realIn-$realOut;
        return ['opening'=>$settings,'real_in'=>$realIn,'real_out'=>$realOut,'forecast_in'=>$forecastIn,'planned_out'=>$planned,'real_balance'=>$real,'forecast_balance'=>$real+$forecastIn-$planned];
    }

    public function plannableFebs(): array
    {
        return $this->database->query("SELECT feb.id,feb.numero_feb,feb.montant_demande,feb.date_disponibilite,feb.statut,c.nom_client,bc.numero_bc,da.authorization_number,da.beneficiary_name,da.status authorization_status,fp.source_type,fp.invoice_id,fp.amount planned_amount,COALESCE(fp.scheduled_date,da.planned_date) scheduled_date,fp.note,fp.status plan_status FROM fiches_expression_besoin feb JOIN bons_commande bc ON bc.id=feb.bon_commande_id JOIN disbursement_authorizations da ON da.feb_id=feb.id LEFT JOIN client c ON c.id_client=bc.client_id LEFT JOIN feb_funding_plans fp ON fp.feb_id=feb.id WHERE feb.statut='approuve' AND da.status IN('purchase_validation','planned') AND NOT EXISTS(SELECT 1 FROM decaissements d WHERE d.feb_id=feb.id) ORDER BY COALESCE(fp.scheduled_date,da.planned_date,feb.date_disponibilite),feb.id")->fetchAll();
    }

    public function invoices(): array
    {
        return $this->database->query("SELECT f.id,f.numero_facture,f.date_echeance,f.montant_ttc,c.nom_client,GREATEST(f.montant_ttc-COALESCE(p.paid,0),0) remaining FROM factures_clients f JOIN bons_commande bc ON bc.id=f.bon_commande_id LEFT JOIN client c ON c.id_client=bc.client_id LEFT JOIN (SELECT facture_id,SUM(montant) paid FROM encaissements GROUP BY facture_id)p ON p.facture_id=f.id WHERE f.statut<>'annulee' AND GREATEST(f.montant_ttc-COALESCE(p.paid,0),0)>0 ORDER BY f.date_echeance,f.id")->fetchAll();
    }

    public function points(): array
    {
        return $this->database->query("SELECT p.*,CONCAT_WS(' ',uc.prenom,uc.nom) closed_name,CONCAT_WS(' ',us.prenom,us.nom) signed_name,CONCAT_WS(' ',ue.prenom,ue.nom) executed_name,(SELECT COUNT(*) FROM daily_disbursement_point_lines l WHERE l.point_id=p.id) line_count FROM daily_disbursement_points p LEFT JOIN user_devis uc ON uc.id=p.closed_by LEFT JOIN user_devis us ON us.id=p.dg_signed_by LEFT JOIN user_devis ue ON ue.id=p.executed_by ORDER BY p.point_date DESC,p.id DESC")->fetchAll();
    }

    public function point(int $id): ?array
    {
        $s=$this->database->prepare("SELECT p.*,CONCAT_WS(' ',uc.prenom,uc.nom) closed_name,CONCAT_WS(' ',us.prenom,us.nom) signed_name,CONCAT_WS(' ',ue.prenom,ue.nom) executed_name FROM daily_disbursement_points p LEFT JOIN user_devis uc ON uc.id=p.closed_by LEFT JOIN user_devis us ON us.id=p.dg_signed_by LEFT JOIN user_devis ue ON ue.id=p.executed_by WHERE p.id=:id");$s->execute(['id'=>$id]);$point=$s->fetch();if(!$point)return null;
        $l=$this->database->prepare("SELECT l.*,feb.numero_feb,c.nom_client,bc.numero_bc,f.numero_facture FROM daily_disbursement_point_lines l JOIN fiches_expression_besoin feb ON feb.id=l.feb_id JOIN bons_commande bc ON bc.id=feb.bon_commande_id LEFT JOIN client c ON c.id_client=bc.client_id LEFT JOIN factures_clients f ON f.id=l.invoice_id WHERE l.point_id=:id ORDER BY l.id");$l->execute(['id'=>$id]);$point['lines']=$l->fetchAll();return $point;
    }
}
