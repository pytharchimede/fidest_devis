<?php
declare(strict_types=1);
use App\Http\Controller\LegacyPageController;
use App\Http\Controller\WorkflowController;
use App\Http\Controller\NeedRequestController;
use App\Http\Router;

return static function(Router $router):void {
    $router->get('/',[LegacyPageController::class,'show'],'home');
    $pages=['dashboard'=>'dashboard.php','clients'=>'liste_client.php','appels-offres'=>'liste_offre.php','devis'=>'liste_devis.php','bons-commande'=>'bons_commande.php','affaires'=>'suivi_affaires.php','factures'=>'liste_facture.php','bons-livraison'=>'liste_bl.php','archives'=>'archives.php'];
    foreach($pages as $path=>$target){
        $router->get('/'.$path,static fn($request)=>App\Http\Response::redirect($target.($request->query()?'?'.http_build_query($request->query()):'')),'page.'.$path);
    }
    $router->get('/legacy/{page}',[LegacyPageController::class,'show'],'legacy.page');
    $router->get('/api/workflow/summary',[WorkflowController::class,'summary'],'api.workflow.summary');
    $router->get('/feb',[NeedRequestController::class,'index'],'feb.index');
    $router->get('/feb/validation',[NeedRequestController::class,'validation'],'feb.validation');
    $router->get('/feb/{id}/pdf',[NeedRequestController::class,'pdf'],'feb.pdf');
};
