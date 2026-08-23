<?php
declare(strict_types=1);
require_once __DIR__.'/../auth_check.php';require_once __DIR__.'/../bootstrap.php';
use App\Domain\Billing\BillingRepository;
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
(new BillingRepository(app_database()))->schedule((int)($_POST['devis_id']??0),(string)($_POST['date_facturation']??''));
header('Location: ../liste_facture.php?updated=1');
