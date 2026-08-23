<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Application\Quote\CreateQuote;
use App\Application\Quote\LogoUploader;
use App\Domain\Quote\QuoteRepository;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

try {
    $database = app_database();
    $repository = new QuoteRepository($database);
    $logo = (new LogoUploader(APP_ROOT . '/logo'))->upload($_FILES['logo'] ?? null);
    $issuer = app_branding()->issuerBlock();

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $clientStatement = $database->prepare('SELECT nom_client, localisation_client, commune_client, bp_client, pays_client FROM client WHERE id_client = :id');
    $clientStatement->execute(['id' => $clientId]);
    $client = $clientStatement->fetch();
    $recipient = $client ? implode("\n", array_filter([
        $client['nom_client'],
        $client['localisation_client'],
        $client['commune_client'],
        $client['bp_client'],
        $client['pays_client'],
    ])) : trim((string) ($_POST['destineA'] ?? ''));

    $quote = [
        'number' => '',
        'delivery_time' => trim((string) ($_POST['delaiLivraison'] ?? '')),
        'issued_at' => (string) ($_POST['dateEmission'] ?? ''),
        'expires_at' => (string) ($_POST['dateExpiration'] ?? ''),
        'billing_at' => (string) ($_POST['dateFacturation'] ?? '') ?: null,
        'issuer' => $issuer,
        'recipient' => $recipient,
        'terms' => trim((string) ($_POST['termesConditions'] ?? '')),
        'footer' => trim((string) ($_POST['piedDePage'] ?? '')) ?: app_branding()->footerBlock(),
        'total_excluding_tax' => (float) ($_POST['totalHT'] ?? 0),
        'total_including_tax' => (float) ($_POST['totalTTC'] ?? 0),
        'logo' => $logo,
        'client_id' => $clientId,
        'offer_id' => (int) ($_POST['offre_id'] ?? 0),
        'taxable' => (int) ($_POST['tvaFacturable'] ?? 0),
        'published' => (int) ($_POST['publierDevis'] ?? 0),
        'tax' => (float) ($_POST['tvaTotal'] ?? 0),
        'contact' => trim((string) ($_POST['correspondant'] ?? '')),
        'created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
    ];

    $lines = [];
    $designations = (array) ($_POST['designation'] ?? []);
    $lineTaxes = (array) ($_POST['tva'] ?? []);
    foreach ($designations as $index => $designation) {
        if (trim((string) $designation) === '') {
            continue;
        }
        $lines[] = [
            'description' => trim((string) $designation),
            'price' => (float) (($_POST['prix'] ?? [])[$index] ?? 0),
            'quantity' => (float) (($_POST['quantite'] ?? [])[$index] ?? 0),
            'tax' => (float) ($lineTaxes[$index] ?? 0),
            'discount' => (float) (($_POST['remise'] ?? [])[$index] ?? 0),
            'total' => (float) (($_POST['total'] ?? [])[$index] ?? 0),
        ];
    }

    $quoteId = (new CreateQuote($database, $repository))->execute($quote, $lines);
    $_SESSION['devisId'] = $quoteId;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'quote_id' => $quoteId], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Impossible d’enregistrer le devis. Vérifiez la configuration de la base de données.',
    ], JSON_THROW_ON_ERROR);
}
