<?php
declare(strict_types=1);
namespace App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
final class LegacyPageController
{
    private const PAGES=['dashboard'=>'dashboard.php','clients'=>'liste_client.php','offers'=>'liste_offre.php','quotes'=>'liste_devis.php','orders'=>'bons_commande.php','workflow'=>'suivi_affaires.php','invoices'=>'liste_facture.php','deliveries'=>'liste_bl.php','archives'=>'archives.php'];
    public function show(Request $request,array $parameters):Response
    {
        $page=(string)($parameters['page']??'');$target=self::PAGES[$page]??'dashboard.php';$query=$request->query();
        return Response::redirect($target.($query?'?'.http_build_query($query):''));
    }
}
