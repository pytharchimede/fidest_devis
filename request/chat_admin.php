<?php
declare(strict_types=1);
require_once __DIR__.'/../auth_check.php';
require_once __DIR__.'/../bootstrap.php';
use App\Application\Chat\ChatFileUploader;
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store, private');
$pdo=app_database();$conversationId=(int)($_GET['conversation_id']??0);
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals((string)($_SESSION['admin_chat_csrf']??''),(string)($_POST['csrf']??'')))throw new RuntimeException('Session expirée');
  $conversationId=(int)($_POST['conversation_id']??0);if($conversationId<1)throw new RuntimeException('Conversation invalide.');
  if(($_POST['action']??'')==='request_contact'){
   $type=(string)($_POST['contact_type']??'');if(!in_array($type,['telephone','whatsapp'],true))throw new RuntimeException('Type de contact invalide.');
   $label=$type==='whatsapp'?'son numéro WhatsApp':'son numéro de téléphone';$pdo->beginTransaction();
   $pdo->prepare('UPDATE conversations_boutique SET contact_request=?,contact_requested_at=NOW(),assigned_user_id=?,updated_at=NOW() WHERE id=?')->execute([$type,(int)$_SESSION['user_id'],$conversationId]);
   $pdo->prepare("INSERT INTO messages_boutique(conversation_id,expediteur,user_id,message) VALUES(?,'utilisateur',?,?)")->execute([$conversationId,(int)$_SESSION['user_id'],'Demande de coordonnées : le conseiller souhaite recevoir '.$label.'.']);
   $pdo->commit();echo json_encode(['success'=>true]);exit;
  }
  $message=trim((string)($_POST['message']??''));$uploads=ChatFileUploader::uploadMany($_FILES['files']??[],dirname(__DIR__));if($message===''&&!$uploads)throw new RuntimeException('Ajoutez un message ou un fichier.');
  $insert=$pdo->prepare("INSERT INTO messages_boutique(conversation_id,expediteur,user_id,message,fichier,fichier_original,mime_type) VALUES(?,'utilisateur',?,?,?,?,?)");
  if(!$uploads)$insert->execute([$conversationId,(int)$_SESSION['user_id'],$message,null,null,null]);foreach($uploads as$i=>$upload)$insert->execute([$conversationId,(int)$_SESSION['user_id'],$i===0?$message:'',$upload['path'],$upload['original'],$upload['mime']]);
  $pdo->prepare("UPDATE conversations_boutique SET assigned_user_id=?,statut='ouverte',updated_at=NOW() WHERE id=?")->execute([(int)$_SESSION['user_id'],$conversationId]);echo json_encode(['success'=>true]);
 }catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();echo json_encode(['error'=>$e->getMessage()]);}exit;
}
if($conversationId>0){
 $pdo->prepare("UPDATE messages_boutique SET lu_at=NOW() WHERE conversation_id=? AND expediteur='visiteur' AND lu_at IS NULL")->execute([$conversationId]);
 $s=$pdo->prepare("SELECT m.id,m.expediteur,m.message,m.fichier,m.fichier_original,m.mime_type,m.created_at,TRIM(CONCAT(COALESCE(u.prenom,''),' ',COALESCE(u.nom,''))) agent,u.photo agent_photo,u.fonction agent_fonction FROM messages_boutique m LEFT JOIN user_devis u ON u.id=m.user_id WHERE m.conversation_id=? ORDER BY m.id");$s->execute([$conversationId]);
 $c=$pdo->prepare('SELECT nom_visiteur,email_visiteur,telephone_visiteur,whatsapp_visiteur,contact_request FROM conversations_boutique WHERE id=?');$c->execute([$conversationId]);
 echo json_encode(['messages'=>$s->fetchAll(PDO::FETCH_ASSOC),'visitor'=>$c->fetch(PDO::FETCH_ASSOC)?:null],JSON_UNESCAPED_UNICODE);exit;
}echo json_encode(['messages'=>[],'visitor'=>null]);
