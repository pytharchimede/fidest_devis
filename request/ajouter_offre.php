<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Domain\Offer\OfferRepository;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../liste_offre.php');
    exit;
}

(new OfferRepository(app_database()))->create([
    'number' => trim((string) ($_POST['num_offre'] ?? '')),
    'offer_date' => (string) ($_POST['date_offre'] ?? ''),
    'reference' => trim((string) ($_POST['reference_offre'] ?? '')),
    'sales_contact' => trim((string) ($_POST['commercial_dedie'] ?? '')),
    'created_at' => (string) ($_POST['date_creat_offre'] ?? ''),
]);

header('Location: ../liste_offre.php');
exit;
