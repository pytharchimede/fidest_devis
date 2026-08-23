<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
use App\Domain\Offer\OfferRepository;
$repository = new OfferRepository(app_database());
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$offer = $repository->find($id);
if (!$offer) { http_response_code(404); exit('Offre introuvable.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repository->update($id, ['number'=>trim((string)$_POST['num_offre']),'offer_date'=>(string)$_POST['date_offre'],
        'reference'=>trim((string)$_POST['reference_offre']),'sales_contact'=>trim((string)$_POST['commercial_dedie']),
        'created_at'=>(string)$_POST['date_creat_offre']]);
    header('Location: liste_offre.php'); exit;
}
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Modifier l’offre</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/modules.css"></head><body class="module-page"><div class="container"><div class="module-heading"><div class="module-heading__copy"><div><h1>Modifier l’offre</h1><p>Actualisez la référence et le suivi commercial.</p></div></div><a href="liste_offre.php" class="btn btn-outline-secondary">Retour</a></div><form method="post" class="card p-4"><input type="hidden" name="id" value="<?= $id ?>"><div class="row g-3"><div class="col-md-6"><label class="form-label">Numéro</label><input class="form-control" name="num_offre" value="<?= e($offer['num_offre']) ?>" required></div><div class="col-md-6"><label class="form-label">Référence</label><input class="form-control" name="reference_offre" value="<?= e($offer['reference_offre']) ?>" required></div><div class="col-md-6"><label class="form-label">Date de l’offre</label><input type="date" class="form-control" name="date_offre" value="<?= e($offer['date_offre']) ?>" required></div><div class="col-md-6"><label class="form-label">Commercial dédié</label><input class="form-control" name="commercial_dedie" value="<?= e($offer['commercial_dedie']) ?>" required></div><div class="col-md-6"><label class="form-label">Date de création</label><input type="date" class="form-control" name="date_creat_offre" value="<?= e($offer['date_creat_offre']) ?>" required></div><div class="col-12"><button class="btn btn-primary">Enregistrer les modifications</button></div></div></form></div></body></html>
