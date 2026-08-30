<?php
declare(strict_types=1);
use App\Http\Router;
return (new Router())->get('/dashboard',static fn()=>['legacy_entry'=>'dashboard.php']);
