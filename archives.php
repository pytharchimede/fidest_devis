<?php

declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';

use App\Application\Archive\CloseExercise;

$db = app_database();
function ah(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$permission = $db->prepare('SELECT gestion_utilisateur FROM user_devis WHERE id=:id AND active=1');
$permission->execute(['id' => (int)($_SESSION['user_id'] ?? 0)]);
$canClose = (bool)$permission->fetchColumn();
$_SESSION['archive_csrf'] ??= bin2hex(random_bytes(24));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') try {
    if (!$canClose) throw new RuntimeException('Vous n’avez pas l’autorisation de clôturer un exercice.');
    if (!hash_equals($_SESSION['archive_csrf'], (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Session expirée.');
    if (($_POST['confirmation'] ?? '') !== '1') throw new RuntimeException('Confirmez la procédure d’archivage.');
    $run = (new CloseExercise($db, APP_ROOT))->execute((string)($_POST['exercise_start'] ?? ''), (int)$_SESSION['user_id']);
    $_SESSION['archive_csrf'] = bin2hex(random_bytes(24));
    header('Location: archives.php?created=' . $run);
    exit;
} catch (Throwable $e) {
    $error = $e->getMessage();
}
$current = $db->query("SELECT setting_value FROM app_settings WHERE setting_key='exercise_start_date'")->fetchColumn();
$runs = $db->query("SELECT r.*,TRIM(CONCAT(u.prenom,' ',u.nom)) auteur FROM archive_runs r LEFT JOIN user_devis u ON u.id=r.created_by ORDER BY r.id DESC")->fetchAll();
$selected = (int)($_GET['run'] ?? 0);
$details = [];
if ($selected) {
    $s = $db->prepare('SELECT o.num_offre,o.reference_offre,o.date_offre,c.nom_client,COUNT(DISTINCT d.id) devis,COALESCE(SUM(d.total_ttc),0) total FROM archive_offre o LEFT JOIN archive_client c ON c.id_client=o.client_id AND c.archive_run_id_copy=:client_run LEFT JOIN archive_devis d ON d.offre_id=o.id_offre AND d.archive_run_id_copy=:devis_run WHERE o.archive_run_id_copy=:offer_run GROUP BY o.id_offre ORDER BY o.date_offre DESC');
    $s->execute(['client_run' => $selected, 'devis_run' => $selected, 'offer_run' => $selected]);
    $details = $s->fetchAll();
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Exercices et archives · FIDEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .archive-hero {
            padding: 30px;
            color: #fff;
            background: linear-gradient(125deg, var(--brand-primary), var(--brand-primary-soft));
            border-radius: 24px
        }

        .archive-hero h1 {
            color: #fff !important
        }

        .archive-grid {
            display: grid;
            grid-template-columns: .8fr 1.2fr;
            gap: 20px;
            margin-top: 20px
        }

        .archive-panel {
            padding: 24px;
            background: #fff;
            border: 1px solid var(--brand-border);
            border-radius: 19px
        }

        .run {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px;
            border: 1px solid var(--brand-border);
            border-radius: 13px
        }

        .run+.run {
            margin-top: 9px
        }

        @media(max-width:850px) {
            .archive-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body class="module-page"><?php include __DIR__ . '/partials/navbar.php'; ?><main class="container">
        <section class="archive-hero">
            <div class="small fw-bold text-warning">CLÔTURE CONTRÔLÉE</div>
            <h1>Exercices et archives</h1>
            <p class="mb-0">Les données clôturées sont copiées table par table, conservées et retirées uniquement des vues actives.</p>
        </section><?php if ($error): ?><div class="alert alert-danger mt-3"><?= ah($error) ?></div><?php elseif (isset($_GET['created'])): ?><div class="alert alert-success mt-3">L’exercice a été clôturé et les copies d’archives ont été vérifiées dans la même transaction.</div><?php endif; ?><div class="archive-grid">
            <section class="archive-panel">
                <h2 class="h5"><i class="fa-solid fa-calendar-check text-warning me-2"></i>Début d’exercice</h2>
                <p>Exercice actif : <strong><?= ah($current ?: 'Non défini') ?></strong></p><?php if ($canClose): ?><form method="post"><input type="hidden" name="csrf" value="<?= ah($_SESSION['archive_csrf']) ?>"><label class="form-label">Nouvelle date de début</label><input class="form-control" type="date" name="exercise_start" min="<?= ah($current ?: '2000-01-01') ?>" max="<?= date('Y-m-d') ?>" required>
                        <div class="alert alert-warning mt-3 small"><i class="fa-solid fa-triangle-exclamation"></i> Toutes les affaires antérieures seront copiées dans les tables d’archives puis masquées des écrans actifs. Aucun enregistrement source ne sera supprimé.</div><label class="form-check"><input class="form-check-input" type="checkbox" name="confirmation" value="1" required><span class="form-check-label">Je confirme la clôture de l’exercice précédent.</span></label><button class="btn btn-primary w-100 mt-3" onclick="return confirm('Confirmer définitivement cette clôture ?')"><i class="fa-solid fa-box-archive me-2"></i>Clôturer et archiver</button>
                    </form><?php else: ?><div class="alert alert-info">La clôture est réservée aux administrateurs. Les archives restent consultables.</div><?php endif; ?>
            </section>
            <section class="archive-panel">
                <h2 class="h5"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Historique des clôtures</h2><?php if (!$runs): ?><p class="text-muted">Aucun exercice clôturé.</p><?php endif; ?><?php foreach ($runs as $r): ?><div class="run">
                        <div><strong>Exercice ouvert le <?= ah($r['exercise_start_date']) ?></strong><br><small><?= ah($r['archived_offers']) ?> AO · <?= ah($r['archived_quotes']) ?> devis · par <?= ah($r['auteur'] ?: 'Système') ?></small></div><a class="btn btn-sm btn-outline-primary" href="archives.php?run=<?= $r['id'] ?>"><i class="fa-solid fa-eye"></i> Consulter</a>
                    </div><?php endforeach; ?>
            </section>
        </div><?php if ($selected): ?><section class="archive-panel mt-4">
                <h2 class="h5">Contenu de l’archive #<?= $selected ?></h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Appel d’offre</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Devis</th>
                                <th>Volume historique</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($details as $d): ?><tr>
                                    <td><strong><?= ah($d['num_offre']) ?></strong><br><small><?= ah($d['reference_offre']) ?></small></td>
                                    <td><?= ah($d['nom_client'] ?: '—') ?></td>
                                    <td><?= ah($d['date_offre']) ?></td>
                                    <td><?= (int)$d['devis'] ?></td>
                                    <td><?= number_format((float)$d['total'], 0, ',', ' ') ?> FCFA</td>
                                </tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            </section><?php endif; ?>
    </main>
</body>

</html>