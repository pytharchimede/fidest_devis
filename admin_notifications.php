<?php

declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';

$pdo = app_database();
$permission = $pdo->prepare('SELECT gestion_utilisateur FROM user_devis WHERE id = :id AND active = 1');
$permission->execute(['id' => (int) ($_SESSION['user_id'] ?? 0)]);
if (!(bool) $permission->fetchColumn()) {
    http_response_code(403);
    exit('Accès refusé.');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function sqlDate(string $value): ?string
{
    return $value === '' ? null : str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['message'] ?? ''));
    $start = sqlDate(trim((string) ($_POST['starts_at'] ?? ''))) ?: date('Y-m-d H:i:s');
    $end = sqlDate(trim((string) ($_POST['ends_at'] ?? '')));
    if ($title === '' || $body === '') {
        $message = 'Le titre et le message sont obligatoires.';
    } else {
        $statement = $pdo->prepare('INSERT INTO app_announcements(title,message,url,starts_at,ends_at,active,created_by) VALUES(:title,:message,:url,:starts_at,:ends_at,:active,:created_by)');
        $statement->execute(['title' => $title, 'message' => $body, 'url' => trim((string) ($_POST['url'] ?? '')) ?: null, 'starts_at' => $start, 'ends_at' => $end, 'active' => isset($_POST['active']) ? 1 : 0, 'created_by' => (int) $_SESSION['user_id']]);
        header('Location: admin_notifications.php?created=1');
        exit;
    }
}
$announcements = $pdo->query('SELECT id,title,message,starts_at,ends_at,active FROM app_announcements ORDER BY starts_at DESC,id DESC')->fetchAll();
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Annonces et notifications</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="module-page"><?php include __DIR__ . '/partials/navbar.php'; ?><div class="container py-4">
        <div class="module-heading">
            <div class="module-heading__copy"><span class="module-heading__icon"><i class="fa-solid fa-bullhorn"></i></span>
                <div>
                    <h1>Annonces et notifications</h1>
                    <p>Programmez les informations qui apparaîtront dans la cloche de l’équipe.</p>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-5">
                <section class="card p-4">
                    <h2 class="h5">Nouvelle annonce</h2><?php if ($message): ?><div class="alert alert-danger"><?= h($message) ?></div><?php endif; ?><form method="post"><label class="form-label" for="title">Titre</label><input class="form-control mb-3" id="title" name="title" required><label class="form-label" for="message">Message</label><textarea class="form-control mb-3" id="message" name="message" rows="5" required></textarea><label class="form-label" for="url">Lien facultatif</label><input class="form-control mb-3" id="url" name="url" placeholder="dashboard.php"><label class="form-label" for="starts_at">Afficher à partir du</label><input class="form-control mb-3" type="datetime-local" id="starts_at" name="starts_at"><label class="form-label" for="ends_at">Jusqu’au</label><input class="form-control mb-3" type="datetime-local" id="ends_at" name="ends_at"><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="active" checked> Annonce active</label><button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-2"></i>Programmer l’annonce</button></form>
                </section>
            </div>
            <div class="col-lg-7">
                <section class="card p-4">
                    <h2 class="h5">Annonces programmées</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody><?php foreach ($announcements as $announcement): ?><tr>
                                        <td><strong><?= h($announcement['title']) ?></strong><br><small><?= h($announcement['message']) ?></small></td>
                                        <td><?= h($announcement['starts_at']) ?></td>
                                        <td><?= h((string) ($announcement['ends_at'] ?? 'Sans limite')) ?></td>
                                        <td><?= $announcement['active'] ? 'Active' : 'Inactive' ?></td>
                                    </tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</body>

</html>