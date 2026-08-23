<?php
declare(strict_types=1);
require_once __DIR__.'/../bootstrap.php';
use App\Application\Chat\ChatFileUploader;
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store, no-cache, must-revalidate, private, max-age=0');header('Pragma: no-cache');header('Expires: 0');header('Vary: Cookie');
$pdo=app_database();if(!isset($_SESSION['public_chat_token'])||!preg_match('/^[a-f0-9]{48}$/',(string)$_SESSION['public_chat_token']))$_SESSION['public_chat_token']=bin2hex(random_bytes(24));$token=(string)$_SESSION['public_chat_token'];
$find=$pdo->prepare('SELECT * FROM conversations_boutique WHERE visitor_token=? LIMIT 1');$find->execute([$token]);$conversation=$find->fetch(PDO::FETCH_ASSOC);
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals((string)($_SESSION['public_chat_csrf']??''),(string)($_POST['csrf']??'')))throw new RuntimeException('Session expirée');
  if(($_POST['action']??'')==='save_contact'){
   if(!$conversation)throw new RuntimeException('Conversation introuvable.');$type=(string)($conversation['contact_request']??'');if(!in_array($type,['telephone','whatsapp'],true))throw new RuntimeException('Aucune coordonnée n’est demandée.');
   $number=trim((string)($_POST['phone']??''));$normalized=preg_replace('/[^0-9+]/','',$number);if(strlen((string)$normalized)<8||strlen((string)$normalized)>20)throw new RuntimeException('Saisissez un numéro valide avec l’indicatif du pays.');
   $column=$type==='whatsapp'?'whatsapp_visiteur':'telephone_visiteur';$pdo->beginTransaction();$pdo->prepare("UPDATE conversations_boutique SET {$column}=?,contact_request=NULL,updated_at=NOW() WHERE id=?")->execute([$number,(int)$conversation['id']]);
   $label=$type==='whatsapp'?'WhatsApp':'téléphone';$pdo->prepare("INSERT INTO messages_boutique(conversation_id,expediteur,message) VALUES(?,'visiteur',?)")->execute([(int)$conversation['id'],'Mon numéro '.$label.' a été transmis au conseiller.']);$pdo->commit();echo json_encode(['success'=>true]);exit;
  }
  $message=trim((string)($_POST['message']??''));$uploads=ChatFileUploader::uploadMany($_FILES['files']??[],dirname(__DIR__));if($message===''&&!$uploads)throw new RuntimeException('Ajoutez un message ou un fichier.');
  if(!$conversation){$pdo->prepare('INSERT INTO conversations_boutique(visitor_token,nom_visiteur,email_visiteur,produit_id) VALUES(?,?,?,?)')->execute([$token,trim((string)($_POST['name']??''))?:null,filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL)?:null,(int)($_POST['product_id']??0)?:null]);$conversation=['id'=>(int)$pdo->lastInsertId()];}
  $insert=$pdo->prepare("INSERT INTO messages_boutique(conversation_id,expediteur,message,fichier,fichier_original,mime_type) VALUES(?,'visiteur',?,?,?,?)");if(!$uploads)$insert->execute([(int)$conversation['id'],$message,null,null,null]);foreach($uploads as$i=>$upload)$insert->execute([(int)$conversation['id'],$i===0?$message:'',$upload['path'],$upload['original'],$upload['mime']]);
  $pdo->prepare("UPDATE conversations_boutique SET statut='ouverte',updated_at=NOW(),nom_visiteur=COALESCE(NULLIF(?,''),nom_visiteur),email_visiteur=COALESCE(NULLIF(?,''),email_visiteur) WHERE id=?")->execute([trim((string)($_POST['name']??'')),filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL)?:'',(int)$conversation['id']]);echo json_encode(['success'=>true]);
 }catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();echo json_encode(['error'=>$e->getMessage()]);}exit;
}
if(!$conversation){echo json_encode(['messages'=>[],'visitor'=>['created'=>false]]);exit;}
$pdo->prepare("UPDATE messages_boutique SET lu_at=NOW() WHERE conversation_id=? AND expediteur='utilisateur' AND lu_at IS NULL")->execute([(int)$conversation['id']]);$s=$pdo->prepare("SELECT m.id,m.expediteur,m.message,m.fichier,m.fichier_original,m.mime_type,m.created_at,TRIM(CONCAT(COALESCE(u.prenom,''),' ',COALESCE(u.nom,''))) agent,u.photo agent_photo,u.fonction agent_fonction FROM messages_boutique m LEFT JOIN user_devis u ON u.id=m.user_id WHERE m.conversation_id=? ORDER BY m.id");$s->execute([(int)$conversation['id']]);
echo json_encode(['messages'=>$s->fetchAll(PDO::FETCH_ASSOC),'visitor'=>['created'=>true,'name'=>(string)($conversation['nom_visiteur']??''),'email'=>(string)($conversation['email_visiteur']??''),'telephone'=>(string)($conversation['telephone_visiteur']??''),'whatsapp'=>(string)($conversation['whatsapp_visiteur']??''),'contact_request'=>(string)($conversation['contact_request']??'')]],JSON_UNESCAPED_UNICODE);
