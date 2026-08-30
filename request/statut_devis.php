<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/auth_check.php';require_once dirname(__DIR__).'/bootstrap.php';
$id=(int)($_GET['devisId']??0);$status=(string)($_GET['statut']??'');
if($id>0&&in_array($status,['en_attente','rejete'],true)){
 $s=app_database()->prepare('UPDATE devis SET statut_devis=:status WHERE id=:id AND validation_generale=0');$s->execute(['status'=>$status,'id'=>$id]);
}
header('Location: ../liste_devis.php');
