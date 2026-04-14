<?php
session_start();
require_once('../model/Database.php');

function load_registry($path)
{
    if (!file_exists($path)) return ['items' => []];
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : ['items' => []];
}
function save_registry($path, $data)
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$registryPath = realpath(__DIR__ . '/../data') . DIRECTORY_SEPARATOR . 'bl_registry.json';
$uploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'bl_signed';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $devisId = (int)($_POST['devisId'] ?? 0);
    if ($devisId <= 0) {
        http_response_code(400);
        echo 'devisId manquant.';
        exit;
    }
    if (!isset($_FILES['bl_file']) || $_FILES['bl_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo 'Fichier BL signé manquant.';
        exit;
    }
    $tmp = $_FILES['bl_file']['tmp_name'];
    $name = $_FILES['bl_file']['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo 'Format non supporté. Autorisés: pdf, jpg, jpeg, png';
        exit;
    }
    $destName = 'bl_signed_' . $devisId . '_' . date('Ymd_His') . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $destName;
    if (!move_uploaded_file($tmp, $destPath)) {
        http_response_code(500);
        echo 'Échec de l\'upload.';
        exit;
    }
    // Mettre à jour le registre
    $reg = load_registry($registryPath);
    // Retirer ancienne entrée si existante pour ce devis
    $reg['items'] = array_values(array_filter($reg['items'], function ($it) use ($devisId) {
        return (int)$it['devisId'] !== $devisId;
    }));
    $reg['items'][] = [
        'devisId' => $devisId,
        'file' => 'bl_signed/' . $destName,
        'signedAt' => date('c')
    ];
    save_registry($registryPath, $reg);

    // Optionnel: verrouiller côté session (UI) en empêchant la modification
    $_SESSION['bl_signed_' . $devisId] = true;

    header('Location: ../liste_devis.php?uploaded_bl=1');
    exit;
}

$devisId = (int)($_GET['devisId'] ?? 0);
// Charger détails du devis si présent, sinon préparer la sélection
$pdo = (new Database())->getConnection();
$devisDetails = null;
if ($devisId > 0 && $pdo) {
    $stmt = $pdo->prepare("SELECT d.id, d.numero_devis, d.date_emission, c.nom_client AS client_nom FROM devis d LEFT JOIN client c ON d.client_id = c.id_client WHERE d.id = ?");
    $stmt->execute([$devisId]);
    $devisDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
// Liste pour sélection si aucun devisId
$devisList = [];
if ($devisId <= 0 && $pdo) {
    $stmt = $pdo->query("SELECT d.id, d.numero_devis, d.date_emission, c.nom_client AS client_nom FROM devis d LEFT JOIN client c ON d.client_id = c.id_client ORDER BY d.id DESC");
    $devisList = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Uploader BL signé — Devis #<?php echo htmlspecialchars((string)$devisId, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <style>
        body {
            background: #f7f8fa;
        }

        .navbar {
            background: linear-gradient(135deg, #1d2b57 0%, #2b3f8a 100%);
        }

        .container-narrow {
            max-width: 900px;
            margin: 0 auto;
        }

        .card-modern {
            border: 1px solid #e6e9ef;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
        }

        .title {
            font-weight: 700;
        }

        .muted {
            color: #6c757d;
        }

        .dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 24px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
            transition: border-color .2s ease, background .2s ease;
            cursor: pointer;
        }

        .dropzone.dragover {
            border-color: #0d6efd;
            background: #f0f7ff;
        }

        .preview {
            display: none;
            margin-top: 12px;
        }

        .preview.visible {
            display: block;
        }

        .preview img {
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #e6e9ef;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-pill {
            border-radius: 999px;
            padding: .6rem 1.1rem;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="img/logo_fidest.png" alt="Logo">
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php include '../menu.php'; ?>
            </div>
        </div>
    </nav>
    <div class="container py-4 container-narrow">
        <div class="card card-modern p-4">
            <h2 class="title mb-2">Uploader le Bon de Livraison signé</h2>
            <p class="muted">Formats acceptés: PDF, JPG, JPEG, PNG. L'upload marquera le devis comme livré et désactivera sa modification côté interface.</p>

            <?php if ($devisId <= 0): ?>
                <form method="get" class="mb-4">
                    <label class="form-label">Sélectionnez le devis à traiter</label>
                    <div class="d-flex gap-2">
                        <select name="devisId" class="form-select" required>
                            <option value="">— Choisir un devis —</option>
                            <?php foreach ($devisList as $d): ?>
                                <option value="<?= (int)$d['id'] ?>">#<?= (int)$d['id'] ?> — <?= htmlspecialchars($d['numero_devis']) ?> — <?= htmlspecialchars($d['client_nom'] ?? '—') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary btn-pill" type="submit">Continuer</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($devisId > 0 && $devisDetails): ?>
                <div class="mb-3 p-3" style="background:#f8fafc;border:1px solid #e6e9ef;border-radius:12px;">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Devis:</strong> #<?= (int)$devisDetails['id'] ?> (<?= htmlspecialchars($devisDetails['numero_devis']) ?>)</div>
                        <div class="col-md-4"><strong>Client:</strong> <?= htmlspecialchars($devisDetails['client_nom'] ?? '—') ?></div>
                        <div class="col-md-4"><strong>Date:</strong> <?= htmlspecialchars($devisDetails['date_devis'] ?? '') ?></div>
                        <div class="col-md-4"><strong>Date émission:</strong> <?= htmlspecialchars($devisDetails['date_emission'] ?? '') ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($devisId > 0): ?>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="devisId" value="<?php echo (int)$devisId; ?>" />
                    <div class="dropzone" id="dz">
                        <div class="text-center">
                            <div class="mb-2"><strong>Glissez-déposez votre fichier ici</strong></div>
                            <div class="muted">ou cliquez pour sélectionner</div>
                        </div>
                        <input type="file" name="bl_file" id="fileInput" class="form-control" style="display:none" accept=".pdf,.jpg,.jpeg,.png" required />
                    </div>
                    <div class="preview" id="preview"></div>
                    <div class="actions mt-3">
                        <button class="btn btn-success btn-pill" type="submit"><i class="fas fa-upload"></i> Uploader et valider</button>
                        <a class="btn btn-secondary btn-pill" href="../liste_devis.php">Annuler</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a2e0e6ad4e.js" crossorigin="anonymous"></script>
    <script>
        const dz = document.getElementById('dz');
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('preview');

        function showPreview(file) {
            preview.classList.add('visible');
            preview.innerHTML = '';
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                preview.appendChild(img);
            } else if (file.type === 'application/pdf') {
                const note = document.createElement('div');
                note.className = 'muted';
                note.textContent = 'PDF sélectionné: ' + file.name;
                preview.appendChild(note);
            } else {
                preview.classList.remove('visible');
            }
        }

        dz.addEventListener('click', () => fileInput.click());
        dz.addEventListener('dragover', (e) => {
            e.preventDefault();
            dz.classList.add('dragover');
        });
        dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
        dz.addEventListener('drop', (e) => {
            e.preventDefault();
            dz.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                fileInput.files = e.dataTransfer.files;
                showPreview(e.dataTransfer.files[0]);
            }
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files[0]) showPreview(fileInput.files[0]);
        });
    </script>
</body>

</html>