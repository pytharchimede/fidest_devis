<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

use App\Http\Request;

try { app_router()->dispatch(Request::capture())->send(); }
catch (Throwable $error) {
    $status=(int)$error->getCode();if($status<400||$status>599)$status=500;
    App\Http\Response::html('<h1>Erreur '.$status.'</h1><p>'.htmlspecialchars($error->getMessage(),ENT_QUOTES,'UTF-8').'</p>',$status)->send();
}
