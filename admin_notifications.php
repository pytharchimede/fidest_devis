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
    <title>Annonces et notifications | FIDEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .admin-notifications-page .announcement-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
            padding: 26px 30px;
            color: #fff;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-soft));
            border-radius: var(--brand-radius-lg);
            box-shadow: 0 18px 40px rgba(34, 37, 75, .18);
        }

        .admin-notifications-page .announcement-hero h2 {
            margin: 0 0 6px;
            color: #fff;
            font: 800 1.35rem var(--brand-font-heading);
        }

        .admin-notifications-page .announcement-hero p {
            max-width: 650px;
            margin: 0;
            color: rgba(255, 255, 255, .72);
        }

        .admin-notifications-page .announcement-hero__icon {
            display: grid;
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            place-items: center;
            color: var(--brand-primary);
            background: var(--brand-accent);
            border-radius: 18px;
            font-size: 1.35rem;
        }

        .admin-notifications-page .admin-card {
            height: 100%;
            padding: 26px;
        }

        .admin-notifications-page .admin-card h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 20px;
            color: var(--brand-primary);
            font: 800 1.12rem var(--brand-font-heading);
        }

        .admin-notifications-page .admin-card h2 i {
            color: var(--brand-accent-hover);
        }

        .admin-notifications-page .schedule-note {
            display: flex;
            gap: 10px;
            margin: 18px 0;
            padding: 13px 14px;
            color: var(--brand-text-muted);
            background: #f4f5f8;
            border-radius: 10px;
            font-size: .78rem;
            line-height: 1.45;
        }

        .admin-notifications-page .schedule-note i {
            color: var(--brand-primary);
            margin-top: 2px;
        }

        .admin-notifications-page .announcement-list {
            display: grid;
            gap: 12px;
        }

        .admin-notifications-page .announcement-item {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: start;
            padding: 16px;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            background: #fff;
        }

        .admin-notifications-page .announcement-item__icon {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            color: var(--brand-primary);
            background: #fff6d8;
            border-radius: 12px;
        }

        .admin-notifications-page .announcement-item h3 {
            margin: 0 0 5px;
            color: var(--brand-text);
            font: 800 .92rem var(--brand-font-heading);
        }

        .admin-notifications-page .announcement-item p {
            margin: 0;
            color: var(--brand-text-muted);
            font-size: .78rem;
            line-height: 1.45;
        }

        .admin-notifications-page .announcement-meta {
            margin-top: 9px;
            color: #8a8d9e;
            font-size: .7rem;
        }

        .admin-notifications-page .status {
            display: inline-flex;
            padding: 5px 9px;
            border-radius: 99px;
            color: #14733f;
            background: #dcf7e8;
            font-size: .68rem;
            font-weight: 800;
        }

        .admin-notifications-page .status.is-off {
            color: #8b3540;
            background: #fbe9eb;
        }

        .admin-notifications-page .empty-state {
            padding: 38px 20px;
            color: var(--brand-text-muted);
            text-align: center;
        }

        .admin-notifications-page .navbar .container {
            width: min(1400px, calc(100% - 32px));
        }

        .admin-notifications-page .form-control {
            min-height: 50px;
            padding: 13px 15px;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            background: #fbfcfe;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .admin-notifications-page textarea.form-control {
            min-height: 132px;
            line-height: 1.55;
        }

        .admin-notifications-page .form-control:focus {
            border-color: var(--brand-accent);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(250, 189, 2, .16);
        }

        .admin-notifications-page .form-check {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--brand-text);
            font-size: .82rem;
            font-weight: 700;
        }

        .admin-notifications-page .form-check-input {
            width: 1.15em;
            height: 1.15em;
            margin: 0;
            border-color: var(--brand-border);
            accent-color: var(--brand-primary);
        }

        @media(max-width:700px) {
            .admin-notifications-page .announcement-hero {
                align-items: flex-start;
                padding: 22px;
            }

            .admin-notifications-page .announcement-hero__icon {
                display: none;
            }

            .admin-notifications-page .admin-card {
                padding: 20px;
            }

            .admin-notifications-page .announcement-item {
                grid-template-columns: 36px minmax(0, 1fr);
            }

            .admin-notifications-page .announcement-item .status {
                grid-column: 2;
                justify-self: start;
            }
        }
    </style>
</head>

<body class="module-page admin-notifications-page">
    <?php include __DIR__ . '/partials/navbar.php'; ?>
    <main class="container py-4">
        <div class="module-heading">
            <div class="module-heading__copy"><span class="module-heading__icon"><i class="fa-solid fa-bullhorn"></i></span>
                <div>
                    <h1>Annonces et notifications</h1>
                    <p>Programmez les informations qui apparaîtront dans la cloche de l’équipe.</p>
                </div>
            </div>
        </div>
        <div class="announcement-hero">
            <div>
                <h2>Diffusez les bonnes informations au bon moment</h2>
                <p>Les annonces apparaissent dans la cloche de l’équipe et peuvent être programmées à l’avance.</p>
            </div><span class="announcement-hero__icon"><i class="fa-solid fa-bullhorn"></i></span>
        </div>
        <div class="row g-4">
            <div class="col-lg-5">
                <section class="card admin-card">
                    <h2><i class="fa-solid fa-pen-to-square"></i>Nouvelle annonce</h2>
                    <?php if ($message): ?><div class="alert alert-danger"><?= h($message) ?></div><?php endif; ?>
                    <form method="post">
                        <label class="form-label" for="title">Titre</label><input class="form-control mb-3" id="title" name="title" value="<?= h((string) ($_POST['title'] ?? '')) ?>" required>
                        <label class="form-label" for="message">Message</label><textarea class="form-control mb-3" id="message" name="message" rows="5" required><?= h((string) ($_POST['message'] ?? '')) ?></textarea>
                        <label class="form-label" for="url">Lien facultatif</label><input class="form-control mb-3" id="url" name="url" value="<?= h((string) ($_POST['url'] ?? '')) ?>" placeholder="dashboard.php">
                        <div class="schedule-note"><i class="fa-regular fa-clock"></i><span>Laissez les dates vides pour afficher l’annonce immédiatement et sans date de fin.</span></div>
                        <label class="form-label" for="starts_at">Afficher à partir du</label><input class="form-control mb-3" type="datetime-local" id="starts_at" name="starts_at" value="<?= h((string) ($_POST['starts_at'] ?? '')) ?>">
                        <label class="form-label" for="ends_at">Jusqu’au</label><input class="form-control mb-3" type="datetime-local" id="ends_at" name="ends_at" value="<?= h((string) ($_POST['ends_at'] ?? '')) ?>">
                        <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="active" checked> Annonce active</label>
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-2"></i>Programmer l’annonce</button>
                    </form>
                </section>
            </div>
            <div class="col-lg-7">
                <section class="card admin-card">
                    <h2><i class="fa-solid fa-list-check"></i>Annonces programmées</h2>
                    <div class="announcement-list">
                        <?php if (!$announcements): ?><div class="empty-state">Aucune annonce programmée.</div><?php endif; ?>
                        <?php foreach ($announcements as $announcement): ?><article class="announcement-item"><span class="announcement-item__icon"><i class="fa-solid fa-bullhorn"></i></span>
                                <div>
                                    <h3><?= h($announcement['title']) ?></h3>
                                    <p><?= h($announcement['message']) ?></p>
                                    <div class="announcement-meta">Du <?= h($announcement['starts_at']) ?> · <?= h((string) ($announcement['ends_at'] ?? 'Sans limite')) ?></div>
                                </div><span class="status<?= $announcement['active'] ? '' : ' is-off' ?>"><?= $announcement['active'] ? 'Active' : 'Inactive' ?></span>
                            </article><?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
