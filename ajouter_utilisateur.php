<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/model/Database.php';
$pdo = Database::getConnection();
$errors = [];
$form = ['mail_pro' => '', 'nom' => '', 'prenom' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail_pro = trim((string) ($_POST['mail_pro'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $form = compact('mail_pro', 'nom', 'prenom');
    if (!filter_var($mail_pro, FILTER_VALIDATE_EMAIL)) $errors[] = 'Saisissez une adresse email professionnelle valide.';
    if (strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($nom === '' || $prenom === '') $errors[] = 'Le nom et le prénom sont obligatoires.';
    $existing = $pdo->prepare('SELECT id FROM user_devis WHERE mail_pro = :mail_pro LIMIT 1');
    $existing->execute(['mail_pro' => $mail_pro]);
    if ($existing->fetch()) $errors[] = 'Cette adresse email est déjà utilisée.';
    if ($errors === []) {
        $permissions = ['modifier_devis', 'visualiser_devis', 'soumettre_devis', 'masquer_devis', 'envoyer_devis', 'valider_devis'];
        $values = array_fill_keys($permissions, 0);
        foreach ($permissions as $permission) $values[$permission] = isset($_POST[$permission]) ? 1 : 0;
        $stmt = $pdo->prepare('INSERT INTO user_devis (mail_pro, password, nom, prenom, modifier_devis, visualiser_devis, soumettre_devis, masquer_devis, envoyer_devis, valider_devis, gestion_utilisateur, active, photo, signature, role_id) VALUES (:mail_pro, :password, :nom, :prenom, :modifier_devis, :visualiser_devis, :soumettre_devis, :masquer_devis, :envoyer_devis, :valider_devis, 0, 1, "", "", 0)');
        $stmt->execute(array_merge(['mail_pro' => $mail_pro, 'password' => hash('sha512', $password), 'nom' => $nom, 'prenom' => $prenom], $values));
        header('Location: liste_utilisateur.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un membre | FIDEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .user-form {
            max-width: 1040px;
            margin: 0 auto
        }

        .user-form .card-body {
            padding: clamp(22px, 4vw, 42px)
        }

        .form-section {
            padding: 24px;
            border: 1px solid var(--brand-border);
            border-radius: var(--brand-radius-md);
            background: #fbfbfd
        }

        .form-section+.form-section {
            margin-top: 22px
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: var(--brand-primary);
            font: 800 1.05rem var(--brand-font-heading)
        }

        .section-heading i {
            color: var(--brand-accent)
        }

        .input-with-icon {
            position: relative
        }

        .input-with-icon>i {
            position: absolute;
            top: 16px;
            left: 15px;
            color: var(--brand-text-muted)
        }

        .input-with-icon .form-control {
            padding-left: 43px
        }

        .permission-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px
        }

        .permission-item {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 64px;
            padding: 12px 14px;
            border: 1px solid var(--brand-border);
            border-radius: var(--brand-radius-sm);
            background: #fff;
            cursor: pointer
        }

        .permission-item:has(input:checked) {
            border-color: var(--brand-accent);
            background: #fffaf0
        }

        .permission-item input {
            width: 1.2em;
            height: 1.2em;
            flex: 0 0 auto;
            accent-color: var(--brand-primary)
        }

        .permission-item span {
            color: var(--brand-text);
            font-size: .88rem;
            font-weight: 700
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px
        }

        @media(max-width:640px) {
            .permission-grid {
                grid-template-columns: 1fr
            }

            .form-section {
                padding: 18px
            }

            .form-actions {
                flex-direction: column-reverse
            }

            .form-actions .btn {
                width: 100%
            }
        }
    </style>
</head>

<body class="module-page team-page">
    <?php include __DIR__ . '/partials/navbar.php'; ?>
    <main class="container">
        <div class="module-heading">
            <div class="module-heading__copy"><span class="module-heading__icon"><i class="fas fa-user-plus"></i></span>
                <div>
                    <h1>Ajouter un membre</h1>
                    <p>Créez un accès et définissez ses responsabilités dans FIDEST.</p>
                </div>
            </div><a href="liste_utilisateur.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Retour à l’équipe</a>
        </div>
        <div class="card user-form">
            <div class="card-body"><?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Vérifiez les informations saisies.</strong>
                        <ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                    </div><?php endif; ?>
                <form action="ajouter_utilisateur.php" method="POST">
                    <div class="form-section">
                        <div class="section-heading"><i class="fas fa-id-card"></i>Identité et accès</div>
                        <div class="row g-3">
                            <div class="col-md-6"><label for="prenom" class="form-label">Prénom</label>
                                <div class="input-with-icon"><i class="fas fa-user"></i><input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($form['prenom']) ?>" autocomplete="given-name" required></div>
                            </div>
                            <div class="col-md-6"><label for="nom" class="form-label">Nom</label>
                                <div class="input-with-icon"><i class="fas fa-user"></i><input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($form['nom']) ?>" autocomplete="family-name" required></div>
                            </div>
                            <div class="col-md-6"><label for="mail_pro" class="form-label">Email professionnel</label>
                                <div class="input-with-icon"><i class="fas fa-envelope"></i><input type="email" class="form-control" id="mail_pro" name="mail_pro" value="<?= htmlspecialchars($form['mail_pro']) ?>" autocomplete="email" required></div>
                            </div>
                            <div class="col-md-6"><label for="password" class="form-label">Mot de passe <small class="text-muted">(8 caractères minimum)</small></label>
                                <div class="input-with-icon"><i class="fas fa-lock"></i><input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" required></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <div class="section-heading"><i class="fas fa-shield-halved"></i>Droits d’accès</div>
                        <div class="permission-grid"><?php $permissionLabels = ['modifier_devis' => 'Modifier les devis', 'visualiser_devis' => 'Visualiser les devis', 'soumettre_devis' => 'Soumettre les devis', 'masquer_devis' => 'Masquer les devis', 'envoyer_devis' => 'Envoyer les devis', 'valider_devis' => 'Valider les devis'];
                                                        foreach ($permissionLabels as $key => $label): ?><label class="permission-item" for="<?= $key ?>"><input class="form-check-input" type="checkbox" id="<?= $key ?>" name="<?= $key ?>"><span><?= $label ?></span></label><?php endforeach; ?></div>
                    </div>
                    <div class="form-actions"><a href="liste_utilisateur.php" class="btn btn-light">Annuler</a><button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Créer le membre</button></div>
                </form>
            </div>
        </div>
    </main>
    <div class="footer">
        <p>&copy; <?= date('Y') ?> FIDEST. Tous droits réservés.</p>
    </div>
</body>

</html>