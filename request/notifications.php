<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';
use App\Domain\Notification\NotificationRepository;
header('Content-Type: application/json; charset=UTF-8');
$repository = new NotificationRepository(app_database());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $repository->mark((int) $_SESSION['user_id'], (string) ($payload['key'] ?? ''), !empty($payload['dismiss']));
    echo json_encode(['success' => true]); exit;
}
$items = $repository->billingAlerts((int) $_SESSION['user_id']);
echo json_encode(['items' => $items, 'unread' => count(array_filter($items, static fn(array $item): bool => !$item['read']))], JSON_UNESCAPED_UNICODE);
