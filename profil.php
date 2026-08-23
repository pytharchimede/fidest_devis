<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
require_once 'model/Database.php';
require_once 'model/User.php';

$pdo = Database::getConnection();
$userModel = new User($pdo);

// Vérifier si l'utilisateur est connecté et récupérer ses données
$userId = $_SESSION['user_id'];

// Récupérer les données de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM user_devis WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $mail = trim((string) ($_POST['mail_pro'] ?? $user['mail_pro']));
    $telephone = trim((string) ($_POST['telephone'] ?? ''));
    $fonction = trim((string) ($_POST['fonction'] ?? ''));
    $departement = trim((string) ($_POST['departement'] ?? ''));
    $adresse = trim((string) ($_POST['adresse'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $password = $_POST['password'];
    if ($password !== '' && $password !== (string) ($_POST['password_confirmation'] ?? '')) $error = 'La confirmation du mot de passe ne correspond pas.';
    $hashedPassword = !empty($password) ? hash("sha512", $password) : $user['password'];

    // Traitement de la photo de profil
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo'];
        $targetDir = "photo/";
        $targetFile = $targetDir . basename($photo['name']);
        move_uploaded_file($photo['tmp_name'], $targetFile);
    } else {
        $targetFile = $user['photo'] ?: ''; // Conserver la photo existante si aucune nouvelle photo n'est envoyée
    }

    // Mise à jour des données de l'utilisateur
    if (!isset($error)) {
        $stmt = $pdo->prepare("UPDATE user_devis SET nom=:nom, prenom=:prenom, mail_pro=:mail, telephone=:telephone, fonction=:fonction, departement=:departement, adresse=:adresse, bio=:bio, password=:password, photo=:photo WHERE id=:id");
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'mail' => $mail,
            'telephone' => $telephone,
            'fonction' => $fonction,
            'departement' => $departement,
            'adresse' => $adresse,
            'bio' => $bio,
            'password' => $hashedPassword,
            'photo' => $targetFile,
            'id' => $userId
        ]);

        // Redirection après modification
        header('Location: profil.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #1d2b57;
        }

        .navbar-brand img {
            height: 50px;
        }

        .navbar-nav .nav-link {
            color: #fff !important;
        }

        .navbar-nav .nav-link.active {
            color: #fabd02 !important;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border-radius: 10px;
        }

        .card-header {
            background-color: #1d2b57;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .form-label {
            color: #1d2b57;
        }

        .form-control {
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .form-control-file {
            padding: 5px;
        }

        .btn-primary {
            background-color: #fabd02;
            border-color: #fabd02;
        }

        .btn-primary:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }

        .photo-profile img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .photo-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-profile {
            position: relative;
        }

        .photo-profile::after {
            content: "\f030";
            position: absolute;
            right: 5px;
            bottom: 8px;
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            color: var(--brand-primary);
            background: var(--brand-accent);
            border: 4px solid #fff;
            border-radius: 50%;
            font-family: "Font Awesome 6 Free";
            font-size: .8rem;
            font-weight: 900;
        }

        .profile-photo-button:focus-within {
            outline: 3px solid rgba(250, 189, 2, .3);
            outline-offset: 3px;
        }

        .photo-preview-status.is-ready {
            color: #14733f !important;
            font-weight: 700;
        }

        .footer {
            background-color: #1d2b57;
            color: white;
            text-align: center;
            padding: 20px;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/social-pro.css">
</head>

<body class="module-page profile-page">
    <!-- Menu de navigation -->
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <!-- Contenu principal -->
    <div class="container">
        <div class="module-heading">
            <div class="module-heading__copy">
                <span class="module-heading__icon"><i class="fas fa-user"></i></span>
                <div>
                    <h1>Mon profil</h1>
                    <p>Mettez à jour vos informations personnelles et votre sécurité.</p>
                </div>
            </div>
            <a href="request/export_profile_pdf.php?id=<?= (int) $userId ?>" class="btn btn-outline-primary" target="_blank"><i class="fas fa-file-pdf me-2"></i>Exporter mon profil</a>
        </div>
        <div class="card profile-card">
            <div class="profile-cover">
                <div class="profile-cover__brand"><span>ESPACE COLLABORATEUR</span><strong>FIDEST</strong></div><i class="fas fa-shapes"></i>
            </div>
            <div class="card-body">
                <form action="profil.php" method="POST" enctype="multipart/form-data">
                    <?php if (isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <div class="row">
                        <div class="col-md-4 text-center photo-preview">
                            <div class="photo-profile">
                                <!-- Affiche la photo actuelle ou Gravatar si pas de photo -->
                                <img id="profilePhotoPreview" src="<?= htmlspecialchars($user['photo'] ?: 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['mail_pro']))) . '?d=mm&s=200', ENT_QUOTES, 'UTF-8') ?>" alt="Aperçu de la photo de profil">
                            </div>
                            <div class="profile-identity">
                                <h2><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
                                <p><?= htmlspecialchars((string)($user['fonction'] ?: 'Collaborateur FIDEST')) ?></p><span class="profile-online"><i class="fas fa-circle"></i> Profil actif</span>
                            </div>
                            <div class="mt-3">
                                <label class="profile-photo-button" for="photoInput"><i class="fas fa-camera"></i> Modifier la photo</label>
                                <input id="photoInput" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                                <small id="photoPreviewStatus" class="photo-preview-status d-block mt-2 text-muted" aria-live="polite">JPG, PNG ou WebP.</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="profile-section-heading"><span><i class="fas fa-address-card"></i></span>
                                <div>
                                    <h3>Informations professionnelles</h3>
                                    <p>Ces informations structurent votre identité dans FIDEST.</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">E-mail professionnel</label><input type="email" class="form-control" name="mail_pro" value="<?= htmlspecialchars($user['mail_pro']) ?>" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Téléphone</label><input type="tel" class="form-control" name="telephone" value="<?= htmlspecialchars((string)($user['telephone'] ?? '')) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Fonction</label><input class="form-control" name="fonction" value="<?= htmlspecialchars((string)($user['fonction'] ?? '')) ?>" placeholder="Ex. Responsable commercial"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Département</label><input class="form-control" name="departement" value="<?= htmlspecialchars((string)($user['departement'] ?? '')) ?>"></div>
                                <div class="col-12 mb-3"><label class="form-label">Adresse professionnelle</label><input class="form-control" name="adresse" value="<?= htmlspecialchars((string)($user['adresse'] ?? '')) ?>"></div>
                                <div class="col-12 mb-3"><label class="form-label">Présentation professionnelle</label><textarea class="form-control" name="bio" rows="4"><?= htmlspecialchars((string)($user['bio'] ?? '')) ?></textarea></div>
                            </div>
                            <div class="profile-section-heading profile-security-heading"><span><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                                <div>
                                    <h3>Sécurité du compte</h3>
                                    <p>Utilisez un mot de passe unique et difficile à deviner.</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Laissez vide pour ne pas modifier">
                            </div>
                            <div class="mb-3"><label class="form-label">Confirmer le nouveau mot de passe</label><input type="password" class="form-control" name="password_confirmation"></div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Enregistrer mon profil</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p>&copy; 2024 Mon Application. Tous droits réservés.</p>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Aperçu immédiat de l'image sélectionnée.
        function updatePhotoPreview(event) {
            const preview = document.getElementById('profilePhotoPreview');
            const status = document.getElementById('photoPreviewStatus');
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                event.target.value = '';
                status.textContent = 'Veuillez sélectionner une image valide.';
                status.classList.remove('is-ready');
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                preview.src = reader.result;
                status.textContent = file.name + ' — aperçu prêt';
                status.classList.add('is-ready');
            };
            reader.readAsDataURL(file);
        }

        document.getElementById('photoInput').addEventListener('change', updatePhotoPreview);
    </script>
</body>

</html>
