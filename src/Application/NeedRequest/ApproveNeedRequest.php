<?php
declare(strict_types=1);
namespace App\Application\NeedRequest;
use PDO;use RuntimeException;
final class ApproveNeedRequest
{
    public function __construct(private readonly PDO $database){}
    public function execute(int $febId,int $userId,int $supplierId,string $supplierName,string $supplierAddress,string $supplierPhone,string $supplierEmail,string $plannedDate):void
    {
        if($supplierId<=0&&trim($supplierName)==='')throw new RuntimeException('Sélectionnez ou créez le fournisseur destinataire du BCI.');
        if($plannedDate!==''&&!self::date($plannedDate))throw new RuntimeException('La date prévisionnelle de décaissement est invalide.');
        $this->database->beginTransaction();try{
            $s=$this->database->prepare('SELECT feb.*,u.signature,u.valider_devis,u.gestion_utilisateur FROM fiches_expression_besoin feb JOIN user_devis u ON u.id=:user AND u.active=1 WHERE feb.id=:id FOR UPDATE');$s->execute(['user'=>$userId,'id'=>$febId]);$feb=$s->fetch();if(!$feb)throw new RuntimeException('FEB ou utilisateur introuvable.');if($feb['statut']!=='a_analyser')throw new RuntimeException('Cette FEB a déjà reçu une décision.');if(!(bool)$feb['valider_devis']&&!(bool)$feb['gestion_utilisateur'])throw new RuntimeException('Vous ne disposez pas du droit de validation FEB.');$signature=(string)$feb['signature'];if($signature===''||!is_file(APP_ROOT.'/'.$signature))throw new RuntimeException('Votre signature de profil est obligatoire pour valider la FEB.');
            if($supplierId<=0)$supplierId=(int)($feb['supplier_id']??0);if($supplierId<=0||(int)($feb['supplier_id']??0)!==$supplierId)throw new RuntimeException('La FEB doit conserver le fournisseur NCC vérifié choisi lors de son édition.');$check=$this->database->prepare('SELECT id FROM suppliers WHERE id=:id AND active=1 AND ncc IS NOT NULL AND ncc_verified_at IS NOT NULL');$check->execute(['id'=>$supplierId]);if(!$check->fetchColumn())throw new RuntimeException('Le fournisseur doit être actif et vérifié auprès de la DGI.');
            $orderNumber='BCI-FID-'.date('Y').'-'.str_pad((string)$febId,5,'0',STR_PAD_LEFT);$orderUid='FIDEST-BCI-'.$febId.'-'.strtoupper(bin2hex(random_bytes(3)));$order=$this->database->prepare('INSERT INTO internal_purchase_orders(order_number,feb_id,supplier_id,amount,delivery_date,created_by,document_uid) VALUES(:number,:feb,:supplier,:amount,:date,:user,:uid)');$order->execute(['number'=>$orderNumber,'feb'=>$febId,'supplier'=>$supplierId,'amount'=>$feb['montant_demande'],'date'=>$plannedDate?:null,'user'=>$userId,'uid'=>$orderUid]);$orderId=(int)$this->database->lastInsertId();
            $authNumber='FD-FID-'.date('Y').'-'.str_pad((string)$febId,5,'0',STR_PAD_LEFT);$authUid='FIDEST-FD-'.$febId.'-'.strtoupper(bin2hex(random_bytes(3)));$supplier=$this->database->prepare('SELECT name FROM suppliers WHERE id=:id');$supplier->execute(['id'=>$supplierId]);$beneficiary=(string)$supplier->fetchColumn();$auth=$this->database->prepare("INSERT INTO disbursement_authorizations(authorization_number,feb_id,internal_order_id,supplier_id,beneficiary_name,amount,planned_date,status,approved_by,approver_signature,document_uid) VALUES(:number,:feb,:order,:supplier,:beneficiary,:amount,:date,'purchase_validation',:user,:signature,:uid)");$auth->execute(['number'=>$authNumber,'feb'=>$febId,'order'=>$orderId,'supplier'=>$supplierId,'beneficiary'=>$beneficiary,'amount'=>$feb['montant_demande'],'date'=>$plannedDate?:null,'user'=>$userId,'signature'=>$signature,'uid'=>$authUid]);
            $this->database->prepare("UPDATE fiches_expression_besoin SET statut='approuve',analyzed_by=:user,analyzed_at=NOW(),analyzed_signature=:signature,approved_by=:user,approved_at=NOW(),approved_signature=:signature,motif_rejet=NULL WHERE id=:id")->execute(['user'=>$userId,'signature'=>$signature,'id'=>$febId]);$this->database->commit();
        }catch(\Throwable $e){if($this->database->inTransaction())$this->database->rollBack();throw $e;}
    }
    private static function date(string $v):bool{$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d!==false&&$d->format('Y-m-d')===$v;}
}
