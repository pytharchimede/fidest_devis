<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Domain\Client\ClientRepository;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../liste_client.php');
    exit;
}

(new ClientRepository(app_database()))->create([
    'code' => trim((string) ($_POST['code_client'] ?? '')),
    'name' => trim((string) ($_POST['nom_client'] ?? '')),
    'location' => trim((string) ($_POST['localisation_client'] ?? '')),
    'city' => trim((string) ($_POST['commune_client'] ?? '')),
    'postal_box' => trim((string) ($_POST['bp_client'] ?? '')),
    'country' => trim((string) ($_POST['pays_client'] ?? '')),
    'created_at' => (string) ($_POST['date_creat_client'] ?? ''),
]);

header('Location: ../liste_client.php');
exit;
