<?php
declare(strict_types=1);
require_once __DIR__.'/auth_check.php';
require_once __DIR__.'/bootstrap.php';
$request=App\Http\Request::capture();
app_container()->get(App\Http\Controller\NeedRequestController::class)->validation($request)->send();
