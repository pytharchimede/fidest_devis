<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';

$pdo = app_database();
$userId = (int) ($_GET['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    exit('Aucun utilisateur spécifié.');
}

$statement = $pdo->prepare('SELECT * FROM user_devis WHERE id = :id');
$statement->execute(['id' => $userId]);
$user = $statement->fetch();
if (!$user) {
    http_response_code(404);
    exit('Utilisateur introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $hashedPassword = $password !== '' ? hash('sha512', $password) : $user['password'];
    $permissions = ['modifier_devis', 'visualiser_devis', 'soumettre_devis', 'masquer_devis', 'envoyer_devis', 'valider_devis', 'gestion_utilisateur'];
    $values = [
        'mail_pro' => trim((string) ($_POST['mail_pro'] ?? '')),
        'password' => $hashedPassword,
        'nom' => trim((string) ($_POST['nom'] ?? '')),
        'prenom' => trim((string) ($_POST['prenom'] ?? '')),
        'id' => $userId,
    ];
    foreach ($permissions as $permission) {
        $values[$permission] = isset($_POST[$permission]) ? 1 : 0;
    }

    $update = $pdo->prepare('UPDATE user_devis SET mail_pro = :mail_pro, password = :password, nom = :nom, prenom = :prenom, modifier_devis = :modifier_devis, visualiser_devis = :visualiser_devis, soumettre_devis = :soumettre_devis, masquer_devis = :masquer_devis, envoyer_devis = :envoyer_devis, valider_devis = :valider_devis, gestion_utilisateur = :gestion_utilisateur WHERE id = :id');
    $update->execute($values);
    header('Location: liste_utilisateur.php?updated=1');
    exit;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$fullName = trim((string) $user['prenom'] . ' ' . (string) $user['nom']);
$permissionLabels = [
    'modifier_devis' => ['Modifier les devis', 'Mettre à jour les documents commerciaux', 'fa-pen-to-square'],
    'visualiser_devis' => ['Visualiser les devis', 'Accéder aux documents et aux aperçus', 'fa-eye'],
    'soumettre_devis' => ['Soumettre les devis', 'Transmettre un devis pour validation', 'fa-paper-plane'],
    'masquer_devis' => ['Masquer les devis', 'Déplacer un devis vers la corbeille', 'fa-eye-slash'],
    'envoyer_devis' => ['Envoyer les devis', 'Partager un devis avec un client', 'fa-envelope'],
    'valider_devis' => ['Valider les devis', 'Approuver les documents commerciaux', 'fa-circle-check'],
    'gestion_utilisateur' => ['Annonces et notifications', 'Programmer les informations de la cloche', 'fa-bullhorn'],
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier <?= h($fullName) ?> | FIDEST</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .user-edit-page .edit-layout {
            display: grid;
            grid-template-columns: minmax(260px, .7fr) minmax(0, 1.3fr);
            gap: 22px;
        }

        .user-edit-page .edit-card {
            height: 100%;
            padding: 28px;
        }

        .user-edit-page .identity-panel {
            color: #fff;
            background: linear-gradient(145deg, var(--brand-primary), var(--brand-primary-soft)) !important;
            border: 0 !important;
        }

        .user-edit-page .identity-avatar {
            display: grid;
            width: 78px;
            height: 78px;
            margin-bottom: 22px;
            place-items: center;
            color: var(--brand-primary);
            background: var(--brand-accent);
            border-radius: 22px;
            font: 800 1.8rem var(--brand-font-heading);
        }

        .user-edit-page .identity-panel h2 {
            margin: 0 0 8px;
            color: #fff;
            font: 800 1.45rem var(--brand-font-heading);
        }

        .user-edit-page .identity-panel p {
            margin: 0;
            color: rgba(255, 255, 255, .72);
            overflow-wrap: anywhere;
        }

        .user-edit-page .identity-meta {
            display: grid;
            gap: 12px;
            margin-top: 34px;
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, .18);
        }

        .user-edit-page .identity-meta span {
            display: block;
            color: rgba(255, 255, 255, .58);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .user-edit-page .identity-meta strong {
            display: block;
            margin-top: 3px;
            color: #fff;
            font-size: .9rem;
        }

        .user-edit-page .form-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 20px;
            color: var(--brand-primary);
            font: 800 1.08rem var(--brand-font-heading);
        }

        .user-edit-page .form-section-title i {
            color: var(--brand-accent-hover);
        }

        .user-edit-page .permission-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .user-edit-page .permission-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-height: 76px;
            padding: 14px;
            border: 1px solid var(--brand-border);
            border-radius: var(--brand-radius-sm);
            background: #fff;
            cursor: pointer;
            transition: .18s ease;
        }

        .user-edit-page .permission-item:hover,
        .user-edit-page .permission-item:has(input:checked) {
            border-color: var(--brand-accent);
            background: #fffaf0;
        }

        .user-edit-page .permission-item input {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            margin-top: 2px;
            accent-color: var(--brand-primary);
        }

        .user-edit-page .permission-item strong,
        .user-edit-page .permission-item small {
            display: block;
        }

        .user-edit-page .permission-item strong {
            color: var(--brand-text);
            font-size: .82rem;
        }

        .user-edit-page .permission-item small {
            margin-top: 4px;
            color: var(--brand-text-muted);
            font-size: .7rem;
            line-height: 1.35;
        }

        .user-edit-page .edit-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
        }

        @media(max-width:800px) {
            .user-edit-page .edit-layout {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:560px) {
            .user-edit-page .permission-grid {
                grid-template-columns: 1fr;
            }

            .user-edit-page .edit-card {
                padding: 20px;
            }

            .user-edit-page .edit-actions {
                flex-direction: column-reverse;
            }

            .user-edit-page .edit-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body class="module-page team-page user-edit-page">
    <?php include __DIR__ . '/partials/navbar.php'; ?>
    <main class="container">
        <div class="module-heading">
            <div class="module-heading__copy">
                <span class="module-heading__icon"><i class="fa-solid fa-user-gear"></i></span>
                <div>
                    <h1>Modifier un membre</h1>
                    <p>Actualisez l’accès et les responsabilités de cette personne.</p>
                </div>
            </div>
            <a href="liste_utilisateur.php" class="btn btn-outline-primary"><i class="fa-solid fa-arrow-left me-2"></i>Retour à l’équipe</a>
        </div>
        <form action="modifier_utilisateur.php?id=<?= $userId ?>" method="POST">
            <div class="edit-layout">
                <aside class="card edit-card identity-panel">
                    <div class="identity-avatar"><?= h(strtoupper(substr((string) $user['prenom'], 0, 1) . substr((string) $user['nom'], 0, 1))) ?></div>
                    <h2><?= h($fullName) ?></h2>
                    <p><?= h((string) $user['mail_pro']) ?></p>
                    <div class="identity-meta">
                        <div><span>Statut</span><strong><?= !empty($user['active']) ? 'Compte actif' : 'Compte désactivé' ?></strong></div>
                        <div><span>Identifiant</span><strong>#<?= $userId ?></strong></div>
                    </div>
                </aside>
                <section class="card edit-card">
                    <h2 class="form-section-title"><i class="fa-solid fa-id-card"></i>Identité et accès</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label class="form-label" for="prenom">Prénom</label><input class="form-control" id="prenom" name="prenom" value="<?= h((string) $user['prenom']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="nom">Nom</label><input class="form-control" id="nom" name="nom" value="<?= h((string) $user['nom']) ?>" required></div>
                        <div class="col-12"><label class="form-label" for="mail_pro">Email professionnel</label><input class="form-control" type="email" id="mail_pro" name="mail_pro" value="<?= h((string) $user['mail_pro']) ?>" required></div>
                        <div class="col-12"><label class="form-label" for="password">Nouveau mot de passe <small class="text-muted">Laissez vide pour conserver l’actuel.</small></label><input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password"></div>
                    </div>
                    <h2 class="form-section-title"><i class="fa-solid fa-shield-halved"></i>Droits d’accès</h2>
                    <div class="permission-grid"><?php foreach ($permissionLabels as $key => [$label, $description, $icon]): ?><label class="permission-item" for="<?= $key ?>"><input type="checkbox" id="<?= $key ?>" name="<?= $key ?>" <?= !empty($user[$key]) ? 'checked' : '' ?>><span><strong><i class="fa-solid <?= $icon ?> me-1"></i><?= h($label) ?></strong><small><?= h($description) ?></small></span></label><?php endforeach; ?></div>
                    <div class="edit-actions"><a href="liste_utilisateur.php" class="btn btn-light">Annuler</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2"></i>Enregistrer les modifications</button></div>
                </section>
            </div>
        </form>
    </main>
</body>

</html>