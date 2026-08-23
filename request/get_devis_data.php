<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/bootstrap.php';

$activity = (new App\Domain\Quote\QuoteRepository(app_database()))->activity();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'labels' => array_column($activity, 'date'),
    'data' => array_map('intval', array_column($activity, 'count')),
], JSON_THROW_ON_ERROR);
