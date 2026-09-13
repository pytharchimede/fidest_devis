<?php
declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Treasury\ExportDisbursementPoint;
use App\Application\Treasury\ManageDisbursementProcess;
use App\Domain\Treasury\TreasuryRepository;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\View\ViewRenderer;
use RuntimeException;

final class TreasuryController
{
    public function __construct(private readonly TreasuryRepository $repository,private readonly ManageDisbursementProcess $manager,private readonly ExportDisbursementPoint $exporter,private readonly ViewRenderer $views) {}
    public function index(Request $request,array $parameters=[]): Response
    {
        $_SESSION['treasury_csrf']??=bin2hex(random_bytes(24));$flash=$_SESSION['treasury_flash']??null;unset($_SESSION['treasury_flash']);
        $u=app_database()->prepare('SELECT id,signature,valider_devis,gestion_utilisateur FROM user_devis WHERE id=:id');$u->execute(['id'=>(int)($_SESSION['user_id']??0)]);
        return Response::html($this->views->render('treasury/index',['summary'=>$this->repository->dashboard(),'febs'=>$this->repository->plannableFebs(),'invoices'=>$this->repository->invoices(),'points'=>$this->repository->points(),'csrf'=>$_SESSION['treasury_csrf'],'user'=>$u->fetch()?:[],'flash'=>$flash]));
    }
    public function action(Request $request,array $parameters=[]): Response
    {
        try{
            if(!hash_equals((string)($_SESSION['treasury_csrf']??''),(string)$request->input('csrf','')))throw new RuntimeException('Votre session a expiré.');
            $uid=(int)($_SESSION['user_id']??0);$action=(string)$request->input('action');
            if($action==='opening')$this->manager->updateOpeningBalance($uid,(float)$request->input('amount'),(string)$request->input('date'));
            elseif($action==='plan')$this->manager->plan((int)$request->input('feb_id'),$uid,(string)$request->input('source_type'),(int)$request->input('invoice_id')?:null,(float)$request->input('amount'),(string)$request->input('scheduled_date'),(string)$request->input('note'));
            elseif($action==='close')$this->manager->closeDay($uid,(string)$request->input('point_date'));
            elseif($action==='sign')$this->manager->sign((int)$request->input('point_id'),$uid,'');
            elseif($action==='execute')$this->manager->execute((int)$request->input('point_id'),$uid,(string)$request->input('payment_reference'));
            else throw new RuntimeException('Action de trésorerie inconnue.');
            $_SESSION['treasury_flash']=['type'=>'success','message'=>'L’opération a été enregistrée avec sa traçabilité.'];$_SESSION['treasury_csrf']=bin2hex(random_bytes(24));
        }catch(\Throwable $e){$_SESSION['treasury_flash']=['type'=>'danger','message'=>$e->getMessage()];}
        return Response::redirect('../planification_decaissements.php');
    }
    public function pdf(Request $request,array $parameters): Response { $file=$this->exporter->pdf((int)($parameters['id']??0));return Response::binary($file['content'],'application/pdf',$file['filename'],$request->query('download')==='1'); }
    public function excel(Request $request,array $parameters): Response { $file=$this->exporter->csv((int)($parameters['id']??0));return Response::binary($file['content'],'application/vnd.ms-excel; charset=UTF-8',$file['filename'],true); }
}
