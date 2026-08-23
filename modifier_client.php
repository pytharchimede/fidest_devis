<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
use App\Domain\Client\ClientRepository;
$repository = new ClientRepository(app_database());
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$client = $repository->find($id);
if (!$client) { http_response_code(404); exit('Client introuvable.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repository->update($id, ['code'=>trim((string)$_POST['code_client']),'name'=>trim((string)$_POST['nom_client']),
        'location'=>trim((string)$_POST['localisation_client']),'city'=>trim((string)$_POST['commune_client']),
        'postal_box'=>trim((string)$_POST['bp_client']),'country'=>trim((string)$_POST['pays_client']),
        'created_at'=>(string)$_POST['date_creat_client']]);
    header('Location: liste_client.php'); exit;
}
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Modifier le client</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/modules.css"></head><body class="module-page"><div class="container"><div class="module-heading"><div class="module-heading__copy"><div><h1>Modifier le client</h1><p>Mettez à jour son identité et ses coordonnées.</p></div></div><a href="liste_client.php" class="btn btn-outline-secondary">Retour</a></div><form method="post" class="card p-4"><input type="hidden" name="id" value="<?= $id ?>"><div class="row g-3"><?php foreach(['code_client'=>'Code client','nom_client'=>'Nom du client','localisation_client'=>'Localisation','commune_client'=>'Commune','bp_client'=>'Boîte postale','pays_client'=>'Pays'] as $name=>$label): ?><div class="col-md-6"><label class="form-label"><?= $label ?></label><input class="form-control" name="<?= $name ?>" value="<?= e($client[$name]) ?>" required></div><?php endforeach; ?><div class="col-md-6"><label class="form-label">Date de création</label><input type="date" class="form-control" name="date_creat_client" value="<?= e($client['date_creat_client']) ?>" required></div><div class="col-12"><button class="btn btn-primary"><i class="fas fa-check"></i> Enregistrer</button></div></div></form></div></body></html>
