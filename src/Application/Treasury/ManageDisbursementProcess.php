<?php
declare(strict_types=1);

namespace App\Application\Treasury;

use PDO;
use RuntimeException;

final class ManageDisbursementProcess
{
    public function __construct(private readonly PDO $database) {}

    public function updateOpeningBalance(int $userId,float $amount,string $date): void
    {
        if(!self::date($date))throw new RuntimeException('La date du solde initial est invalide.');
        $s=$this->database->prepare('UPDATE treasury_settings SET opening_balance=:amount,opening_date=:date,updated_by=:user WHERE id=1');$s->execute(['amount'=>$amount,'date'=>$date,'user'=>$userId]);
    }

    public function plan(int $febId,int $userId,string $sourceType,?int $invoiceId,float $amount,string $date,string $note=''): void
    {
        if(!in_array($sourceType,['real','forecast'],true))throw new RuntimeException('Source de financement invalide.');
        if($amount<=0||!self::date($date))throw new RuntimeException('Montant ou date de décaissement invalide.');
        if($sourceType==='forecast'&&(!$invoiceId||$invoiceId<=0))throw new RuntimeException('Sélectionnez la facture prévisionnelle couvrant la FEB.');
        if($sourceType==='real')$invoiceId=null;
        $this->database->beginTransaction();try{
            $f=$this->database->prepare("SELECT feb.montant_demande,feb.statut,da.id authorization_id FROM fiches_expression_besoin feb JOIN disbursement_authorizations da ON da.feb_id=feb.id AND da.status IN('purchase_validation','planned') WHERE feb.id=:id FOR UPDATE");$f->execute(['id'=>$febId]);$feb=$f->fetch();if(!$feb||$feb['statut']!=='approuve')throw new RuntimeException('La fiche de décaissement autorisée est introuvable.');
            if(abs((float)$feb['montant_demande']-$amount)>.01)throw new RuntimeException('Le montant planifié doit correspondre au montant approuvé de la FEB.');
            if($sourceType==='real'){$available=(float)$this->database->query("SELECT (SELECT opening_balance FROM treasury_settings WHERE id=1)+COALESCE((SELECT SUM(montant) FROM encaissements),0)-COALESCE((SELECT SUM(montant) FROM decaissements),0)-COALESCE((SELECT SUM(amount) FROM feb_funding_plans WHERE status IN('planned','closed') AND source_type='real' AND feb_id<>".$febId."),0)")->fetchColumn();if($available<$amount)throw new RuntimeException('Le solde réel disponible, après réservations, est insuffisant.');}
            if($invoiceId){$i=$this->database->prepare("SELECT montant_ttc-COALESCE((SELECT SUM(montant) FROM encaissements WHERE facture_id=f.id),0)-COALESCE((SELECT SUM(amount) FROM feb_funding_plans WHERE invoice_id=f.id AND status IN('planned','closed') AND feb_id<>:feb),0) remaining FROM factures_clients f WHERE id=:id");$i->execute(['id'=>$invoiceId,'feb'=>$febId]);if((float)$i->fetchColumn()<$amount)throw new RuntimeException('Le reliquat prévisionnel de cette facture, après réservations, est insuffisant.');}
            $s=$this->database->prepare("INSERT INTO feb_funding_plans(feb_id,source_type,invoice_id,amount,scheduled_date,status,note,planned_by) VALUES(:feb,:source,:invoice,:amount,:date,'planned',:note,:user) ON DUPLICATE KEY UPDATE source_type=VALUES(source_type),invoice_id=VALUES(invoice_id),amount=VALUES(amount),scheduled_date=VALUES(scheduled_date),note=VALUES(note),planned_by=VALUES(planned_by),planned_at=NOW(),status=IF(status='planned','planned',status)");$s->execute(['feb'=>$febId,'source'=>$sourceType,'invoice'=>$invoiceId,'amount'=>$amount,'date'=>$date,'note'=>trim($note),'user'=>$userId]);$this->database->prepare("UPDATE disbursement_authorizations SET status='planned',planned_date=:date WHERE id=:id")->execute(['date'=>$date,'id'=>$feb['authorization_id']]);$this->database->commit();
        }catch(\Throwable $e){if($this->database->inTransaction())$this->database->rollBack();throw $e;}
    }

    public function closeDay(int $userId,string $date): int
    {
        if(!self::date($date)||$date>date('Y-m-d'))throw new RuntimeException('La date d’arrêté est invalide ou future.');
        $this->database->beginTransaction();try{
            $exists=$this->database->prepare('SELECT id FROM daily_disbursement_points WHERE point_date=:date FOR UPDATE');$exists->execute(['date'=>$date]);if($id=(int)$exists->fetchColumn()){ $this->database->commit();return $id; }
            $plans=$this->database->prepare("SELECT * FROM feb_funding_plans WHERE scheduled_date=:date AND status='planned' FOR UPDATE");$plans->execute(['date'=>$date]);$rows=$plans->fetchAll();if(!$rows)throw new RuntimeException('Aucun décaissement planifié à arrêter pour cette date.');
            $total=array_sum(array_map(static fn($r)=>(float)$r['amount'],$rows));$number='PJD-'.str_replace('-','',$date);$uid='FIDEST-PJD-'.str_replace('-','',$date).'-'.strtoupper(bin2hex(random_bytes(3)));
            $p=$this->database->prepare("INSERT INTO daily_disbursement_points(point_number,point_date,status,total_amount,closed_by,document_uid) VALUES(:number,:date,'closed',:total,:user,:uid)");$p->execute(['number'=>$number,'date'=>$date,'total'=>$total,'user'=>$userId,'uid'=>$uid]);$id=(int)$this->database->lastInsertId();
            $line=$this->database->prepare("INSERT INTO daily_disbursement_point_lines(point_id,funding_plan_id,feb_id,amount,scheduled_date,source_type,invoice_id,status) VALUES(:point,:plan,:feb,:amount,:date,:source,:invoice,'closed')");foreach($rows as $r)$line->execute(['point'=>$id,'plan'=>$r['id'],'feb'=>$r['feb_id'],'amount'=>$r['amount'],'date'=>$r['scheduled_date'],'source'=>$r['source_type'],'invoice'=>$r['invoice_id']]);
            $this->database->prepare("UPDATE feb_funding_plans SET status='closed' WHERE scheduled_date=:date AND status='planned'")->execute(['date'=>$date]);$this->database->commit();return $id;
        }catch(\Throwable $e){if($this->database->inTransaction())$this->database->rollBack();throw $e;}
    }

    public function sign(int $pointId,int $userId,string $signature): void
    {
        $u=$this->database->prepare('SELECT signature,valider_devis,gestion_utilisateur FROM user_devis WHERE id=:id AND active=1');$u->execute(['id'=>$userId]);$user=$u->fetch();if(!$user||(!(bool)$user['valider_devis']&&!(bool)$user['gestion_utilisateur']))throw new RuntimeException('Seul le DG ou un délégataire autorisé peut signer le point du jour.');
        $path=$signature!==''?$signature:(string)$user['signature'];if($path===''||!is_file(APP_ROOT.'/'.$path))throw new RuntimeException('La signature de profil du signataire est absente.');
        $s=$this->database->prepare("UPDATE daily_disbursement_points SET status='signed',dg_signed_by=:user,dg_signed_at=NOW(),dg_signature=:signature WHERE id=:id AND status='closed'");$s->execute(['user'=>$userId,'signature'=>$path,'id'=>$pointId]);if(!$s->rowCount())throw new RuntimeException('Ce point ne peut plus être signé.');
        $this->database->prepare("UPDATE daily_disbursement_point_lines SET status='signed' WHERE point_id=:id AND status='closed'")->execute(['id'=>$pointId]);
    }

    public function execute(int $pointId,int $userId,string $reference): void
    {
        if(trim($reference)==='')throw new RuntimeException('La référence de paiement est obligatoire.');$this->database->beginTransaction();try{
            $p=$this->database->prepare("SELECT * FROM daily_disbursement_points WHERE id=:id AND status='signed' FOR UPDATE");$p->execute(['id'=>$pointId]);if(!$p->fetch())throw new RuntimeException('Le point doit être signé avant décaissement.');
            $l=$this->database->prepare("SELECT * FROM daily_disbursement_point_lines WHERE point_id=:id AND status='signed' FOR UPDATE");$l->execute(['id'=>$pointId]);$rows=$l->fetchAll();if(!$rows)throw new RuntimeException('Aucune ligne à décaisser.');
            $required=array_sum(array_map(static fn($r)=>(float)$r['amount'],$rows));$available=(float)$this->database->query("SELECT COALESCE((SELECT SUM(CASE WHEN movement_type='transfer_to_available' THEN amount WHEN movement_type IN('return_to_entries','manual_out') THEN -amount ELSE 0 END) FROM treasury_ledger),0)-COALESCE((SELECT SUM(montant) FROM decaissements),0)")->fetchColumn();if($available<$required)throw new RuntimeException('Le disponible réel du jour est insuffisant. Transférez d’abord les fonds des entrées réelles vers le disponible.');
            $insert=$this->database->prepare('INSERT INTO decaissements(feb_id,point_line_id,montant,date_decaissement,reference_paiement) VALUES(:feb,:line,:amount,CURRENT_DATE,:reference)');$update=$this->database->prepare("UPDATE daily_disbursement_point_lines SET status='executed',payment_reference=:reference,disbursement_id=:disbursement WHERE id=:id");foreach($rows as $r){$insert->execute(['feb'=>$r['feb_id'],'line'=>$r['id'],'amount'=>$r['amount'],'reference'=>trim($reference)]);$disbursement=(int)$this->database->lastInsertId();$update->execute(['reference'=>trim($reference),'disbursement'=>$disbursement,'id'=>$r['id']]);$this->database->prepare("UPDATE feb_funding_plans SET status='executed' WHERE id=:id")->execute(['id'=>$r['funding_plan_id']]);$this->database->prepare("UPDATE disbursement_authorizations SET status='executed' WHERE feb_id=:feb")->execute(['feb'=>$r['feb_id']]);}
            $this->database->prepare("UPDATE daily_disbursement_points SET status='executed',executed_by=:user,executed_at=NOW() WHERE id=:id")->execute(['user'=>$userId,'id'=>$pointId]);$this->database->commit();
        }catch(\Throwable $e){if($this->database->inTransaction())$this->database->rollBack();throw $e;}
    }
    private static function date(string $value): bool { $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $d!==false&&$d->format('Y-m-d')===$value; }
}
