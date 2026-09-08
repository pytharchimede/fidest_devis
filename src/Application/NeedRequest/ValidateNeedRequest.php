<?php
declare(strict_types=1);namespace App\Application\NeedRequest;
use PDO;use RuntimeException;
final class ValidateNeedRequest
{
    public function __construct(private readonly PDO $database){}
    public function execute(int $febId,int $userId,string $action,string $reason='',string $signatureSource='profile',string $drawnSignature=''):void
    {
        $statement=$this->database->prepare('SELECT feb.*,u.signature,u.valider_devis,u.gestion_utilisateur FROM fiches_expression_besoin feb JOIN user_devis u ON u.id=:user_id AND u.active=1 WHERE feb.id=:id FOR UPDATE');
        $this->database->beginTransaction();try{$statement->execute(['id'=>$febId,'user_id'=>$userId]);$feb=$statement->fetch();if(!$feb)throw new RuntimeException('FEB ou utilisateur introuvable.');$signature=$this->resolveSignature($feb,$userId,$signatureSource,$drawnSignature);
            if($action==='analyze'){if($feb['statut']!=='a_analyser')throw new RuntimeException('Cette FEB n’est plus en attente d’analyse.');if((int)$feb['prepared_by']===$userId)throw new RuntimeException('Le préparateur ne peut pas analyser sa propre FEB.');$this->database->prepare("UPDATE fiches_expression_besoin SET statut='analyse',analyzed_by=:user,analyzed_at=NOW(),analyzed_signature=:signature,motif_rejet=NULL WHERE id=:id")->execute(['user'=>$userId,'signature'=>$signature,'id'=>$febId]);}
            elseif($action==='approve'){if($feb['statut']!=='analyse'||empty($feb['analyzed_by']))throw new RuntimeException('La FEB doit d’abord être analysée.');if(!(bool)$feb['valider_devis']&&!(bool)$feb['gestion_utilisateur'])throw new RuntimeException('Vous ne disposez pas du droit d’approbation.');if((int)$feb['analyzed_by']===$userId)throw new RuntimeException('L’analyste ne peut pas approuver sa propre analyse.');$this->database->prepare("UPDATE fiches_expression_besoin SET statut='approuve',approved_by=:user,approved_at=NOW(),approved_signature=:signature,motif_rejet=NULL WHERE id=:id")->execute(['user'=>$userId,'signature'=>$signature,'id'=>$febId]);}
            elseif($action==='reject'){if(!in_array($feb['statut'],['a_analyser','analyse'],true))throw new RuntimeException('Cette FEB ne peut plus être rejetée.');if(trim($reason)==='')throw new RuntimeException('Le motif de rejet est obligatoire.');if($feb['statut']==='a_analyser'){$this->database->prepare("UPDATE fiches_expression_besoin SET statut='rejete',motif_rejet=:reason,analyzed_by=:user,analyzed_at=NOW(),analyzed_signature=:signature WHERE id=:id")->execute(['reason'=>trim($reason),'user'=>$userId,'signature'=>$signature,'id'=>$febId]);}else{$this->database->prepare("UPDATE fiches_expression_besoin SET statut='rejete',motif_rejet=:reason,approved_by=:user,approved_at=NOW(),approved_signature=:signature WHERE id=:id")->execute(['reason'=>trim($reason),'user'=>$userId,'signature'=>$signature,'id'=>$febId]);}}
            else throw new RuntimeException('Action de validation inconnue.');$this->database->commit();
        }catch(\Throwable $error){if($this->database->inTransaction())$this->database->rollBack();throw $error;}
    }
    private function resolveSignature(array $user,int $userId,string $source,string $drawn):string
    {
        if($source==='profile'){$path=(string)($user['signature']??'');if($path===''||!is_file(APP_ROOT.'/'.$path))throw new RuntimeException('Votre signature de profil est absente. Dessinez une signature ou enregistrez-en une dans votre profil.');return $path;}
        if($source!=='pad')throw new RuntimeException('Mode de signature invalide.');
        if(!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#',$drawn,$match))throw new RuntimeException('Dessinez votre signature dans le pad avant de valider.');
        $binary=base64_decode($match[1],true)?:'';if($binary===''||strlen($binary)>2*1024*1024)throw new RuntimeException('La signature dessinée est invalide ou trop volumineuse.');
        $directory=APP_ROOT.'/photo/signatures';if(!is_dir($directory)&&!mkdir($directory,0775,true)&&!is_dir($directory))throw new RuntimeException('Le dossier des signatures est indisponible.');
        $relative='photo/signatures/feb_'.$userId.'_'.bin2hex(random_bytes(10)).'.png';if(file_put_contents(APP_ROOT.'/'.$relative,$binary,LOCK_EX)===false)throw new RuntimeException('La signature ne peut pas être enregistrée.');return $relative;
    }
}
