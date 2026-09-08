<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

$_SESSION['feb_validation_csrf'] ??= bin2hex(random_bytes(24));

try {
    $csrf = (string) ($_POST['csrf'] ?? '');

    if (
        !hash_equals(
            (string) $_SESSION['feb_validation_csrf'],
            $csrf
        )
    ) {
        throw new RuntimeException(
            'Votre session a expiré.'
        );
    }

    $febId = (int) ($_POST['feb_id'] ?? 0);
    $action = trim((string) ($_POST['action'] ?? ''));
    $reason = trim((string) ($_POST['reason'] ?? ''));
    $signatureSource = trim((string) ($_POST['signature_source'] ?? 'profile'));
    $drawnSignature = trim((string) ($_POST['signature_data'] ?? ''));

    if ($febId <= 0) {
        throw new RuntimeException(
            'FEB invalide.'
        );
    }

    (new App\Application\NeedRequest\ValidateNeedRequest(
        app_database()
    ))->execute(
        $febId,
        (int) $_SESSION['user_id'],
        $action,
        $reason,
        $signatureSource,
        $drawnSignature
    );

    $_SESSION['feb_flash'] = [
        'type' => 'success',
        'message' => 'La validation de la FEB a été enregistrée.',
    ];

    $_SESSION['feb_validation_csrf'] = bin2hex(
        random_bytes(24)
    );
} catch (Throwable $error) {
    $_SESSION['feb_flash'] = [
        'type' => 'danger',
        'message' => $error->getMessage(),
    ];
}

$returnTo = ($_POST['return_to'] ?? '') === 'validation' ? 'validation_feb.php' : 'fiches_expression_besoin.php';
header('Location: ../' . $returnTo);

exit;
