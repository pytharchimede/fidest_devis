<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Domain\Offer\OfferRepository;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../liste_offre.php');
    exit;
}

$clientId = (int) ($_POST['client_id'] ?? 0);
$deadline = trim((string) ($_POST['date_limite_reponse'] ?? ''));
$status = (string) ($_POST['statut'] ?? 'en_cours');
$allowedStatuses = ['en_cours', 'remporte', 'termine', 'non_remporte'];
$file = $_FILES['fichier_ao'] ?? null;
if ($clientId <= 0 || $deadline === '' || !in_array($status, $allowedStatuses, true)) {
    $_SESSION['offer_error'] = 'Le client, la date limite de réponse et le statut sont obligatoires.';
    header('Location: ../liste_offre.php'); exit;
}
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $_SESSION['offer_error'] = 'Le fichier de l’appel d’offre est obligatoire.';
    header('Location: ../liste_offre.php'); exit;
}
$mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
$extensions = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'];
if ((int) $file['size'] > 15 * 1024 * 1024 || !isset($extensions[$mime])) {
    $_SESSION['offer_error'] = 'Document invalide (PDF, DOCX, JPG ou PNG, 15 Mo maximum).';
    header('Location: ../liste_offre.php'); exit;
}
$directory = dirname(__DIR__) . '/photo/appels_offre';
if (!is_dir($directory)) mkdir($directory, 0775, true);
$storedName = 'ao_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $storedName)) {
    $_SESSION['offer_error'] = 'Impossible d’enregistrer le document.';
    header('Location: ../liste_offre.php'); exit;
}

(new OfferRepository(app_database()))->create([
    'client_id' => $clientId,
    'number' => trim((string) ($_POST['num_offre'] ?? '')),
    'offer_date' => (string) ($_POST['date_offre'] ?? ''),
    'response_deadline' => $deadline,
    'reference' => trim((string) ($_POST['reference_offre'] ?? '')),
    'title' => trim((string) ($_POST['titre_offre'] ?? '')),
    'estimated_amount' => ($_POST['montant_estime'] ?? '') !== '' ? (float)$_POST['montant_estime'] : null,
    'sales_contact' => trim((string) ($_POST['commercial_dedie'] ?? '')),
    'created_at' => (string) ($_POST['date_creat_offre'] ?? ''),
    'status' => $status,
    'loss_reason' => $status === 'non_remporte' ? trim((string) ($_POST['motif_perte'] ?? '')) : null,
    'file_path' => 'photo/appels_offre/' . $storedName,
    'original_name' => basename((string) $file['name']),
]);

header('Location: ../liste_offre.php');
exit;
