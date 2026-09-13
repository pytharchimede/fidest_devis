<?php
declare(strict_types=1);namespace App\Http\Controller;
use App\Application\NeedRequest\ExportNeedRequestPdf;use App\Application\NeedRequest\GetNeedRequestRegistry;use App\Http\Request;use App\Http\Response;use App\Infrastructure\View\ViewRenderer;
final class NeedRequestController
{
    public function __construct(private readonly GetNeedRequestRegistry $registry,private readonly ExportNeedRequestPdf $exporter,private readonly ViewRenderer $views){}
    public function index(Request $request,array $parameters=[]):Response{return Response::html($this->views->render('need-requests/index',['rows'=>$this->registry->execute()]));}
    public function validation(Request $request,array $parameters=[]):Response
    {
        $_SESSION['feb_validation_csrf'] ??= bin2hex(random_bytes(24));
        $user=app_database()->prepare('SELECT id,signature,valider_devis,gestion_utilisateur FROM user_devis WHERE id=:id AND active=1');
        $user->execute(['id'=>(int)($_SESSION['user_id']??0)]);
        $flash=$_SESSION['feb_flash']??null;unset($_SESSION['feb_flash']);
        $rows=$this->registry->execute();$selectedId=(int)$request->query('feb_id',0);$document=$selectedId>0?app_container()->get(\App\Domain\NeedRequest\NeedRequestRepository::class)->document($selectedId):null;
        $suppliers=app_database()->query('SELECT id,name,address,phone,email FROM suppliers WHERE active=1 ORDER BY name')->fetchAll();
        $query=mb_strtolower(trim((string)$request->query('q','')));$status=(string)$request->query('status','pending');$filtered=array_values(array_filter($rows,static function(array $row)use($query,$status):bool{$matchesStatus=$status==='all'||($status==='pending'?$row['statut']==='a_analyser':$row['statut']===$status);$haystack=mb_strtolower(implode(' ',[$row['numero_feb'],$row['numero_bc'],$row['nom_client'],$row['numero_devis'],$row['num_offre']]));return $matchesStatus&&($query===''||str_contains($haystack,$query));}));
        $view=$selectedId>0?'need-requests/validation':'need-requests/validation-list';
        return Response::html($this->views->render($view,['rows'=>$rows,'filtered'=>$filtered,'query'=>$query,'status'=>$status,'document'=>$document,'selectedId'=>$selectedId,'suppliers'=>$suppliers,'user'=>$user->fetch()?:[],'csrf'=>$_SESSION['feb_validation_csrf'],'flash'=>$flash]));
    }
    public function pdf(Request $request,array $parameters):Response{$id=(int)($parameters['id']??0);$uid=(string)app_database()->query('SELECT document_uid FROM fiches_expression_besoin WHERE id='.$id)->fetchColumn();$base=preg_replace('#/request$#','',(string)dirname((string)$request->server('SCRIPT_NAME','')));$url=((string)$request->server('HTTPS','')!==''?'https':'http').'://'.(string)$request->server('HTTP_HOST','localhost').rtrim((string)$base,'/').'/verification_feb.php?id='.$id.'&uid='.rawurlencode($uid);$file=$this->exporter->execute($id,$url);return Response::binary($file['content'],'application/pdf',$file['filename'],$request->query('download')==='1');}
}
