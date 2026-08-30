<?php
declare(strict_types=1);require_once dirname(__DIR__).'/auth_check.php';require_once dirname(__DIR__).'/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Méthode non autorisée.');}
$_SESSION['feb_validation_csrf']??='';
try{if(!hash_equals((string)$_SESSION['feb_validation_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Votre session a expiré.');(new App\Application\NeedRequest\ValidateNeedRequest(app_database()))->execute((int)($_POST['feb_id']??0),(int)$_SESSION['user_id'],(string)($_POST['action']??''),(string)($_POST['reason']??''));$_SESSION['feb_flash']=['type'=>'success','message'=>'La validation de la FEB a été enregistrée.'];}catch(Throwable $error){$_SESSION['feb_flash']=['type'=>'danger','message'=>$error->getMessage()];}
header('Location: ../fiches_expression_besoin.php');exit;
