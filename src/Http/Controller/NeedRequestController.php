<?php
declare(strict_types=1);namespace App\Http\Controller;
use App\Application\NeedRequest\ExportNeedRequestPdf;use App\Application\NeedRequest\GetNeedRequestRegistry;use App\Http\Request;use App\Http\Response;use App\Infrastructure\View\ViewRenderer;
final class NeedRequestController
{
    public function __construct(private readonly GetNeedRequestRegistry $registry,private readonly ExportNeedRequestPdf $exporter,private readonly ViewRenderer $views){}
    public function index(Request $request,array $parameters=[]):Response{return Response::html($this->views->render('need-requests/index',['rows'=>$this->registry->execute()]));}
    public function pdf(Request $request,array $parameters):Response{$id=(int)($parameters['id']??0);$uid=(string)app_database()->query('SELECT document_uid FROM fiches_expression_besoin WHERE id='.$id)->fetchColumn();$base=preg_replace('#/request$#','',(string)dirname((string)$request->server('SCRIPT_NAME','')));$url=((string)$request->server('HTTPS','')!==''?'https':'http').'://'.(string)$request->server('HTTP_HOST','localhost').rtrim((string)$base,'/').'/verification_feb.php?id='.$id.'&uid='.rawurlencode($uid);$file=$this->exporter->execute($id,$url);return Response::binary($file['content'],'application/pdf',$file['filename'],$request->query('download')==='1');}
}
