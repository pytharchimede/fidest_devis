<?php
// Aperçu visuel du PDF avec sélection des pages à supprimer avant génération finale
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/Devis.php';

// Récup paramètre obligatoire
$devisId = isset($_GET['devisId']) ? (int)$_GET['devisId'] : (isset($_SESSION['devisId']) ? (int)$_SESSION['devisId'] : 0);
if ($devisId <= 0) {
    http_response_code(400);
    echo 'Paramètre devisId manquant.';
    exit;
}
$_SESSION['devisId'] = $devisId; // standardiser

// Options d’affichage
$initialDeletePages = isset($_GET['deletePages']) ? trim((string)$_GET['deletePages']) : '';
$download = isset($_GET['download']) && $_GET['download'] == '1';

// URL de génération (inline ou download)
$exportBase = 'export_pdf.php?devisId=' . urlencode((string)$devisId);
$exportInlineUrl = $exportBase; // inline par défaut
$exportDownloadUrl = $exportBase . '&download=1';

// Petit CSS local minimal pour un rendu propre
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Aperçu PDF - Devis #<?php echo htmlspecialchars((string)$devisId, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            --bg: #f7f8fa;
            --card: #ffffff;
            --accent: #0d6efd;
            --muted: #6c757d;
            --border: #e6e9ef;
            --danger: #dc3545;
            --success: #198754;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: var(--bg);
            color: #1d2125;
        }

        .container {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            font-weight: 600;
            font-size: 16px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 16px;
            padding: 16px;
        }

        .preview {
            height: 75vh;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .preview iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .panel-section {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .muted {
            color: var(--muted);
            font-size: 12px;
        }

        .btn {
            appearance: none;
            border: 1px solid var(--border);
            background: #fff;
            color: #111;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .btn.success {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }

        .btn.danger {
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }

        .btn-block {
            width: 100%;
        }

        .checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px dashed var(--border);
            border-radius: 999px;
        }

        input[type="number"],
        input[type="text"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .small {
            font-size: 12px;
        }

        .notice {
            background: #fff8db;
            border: 1px solid #ffe69c;
            color: #664d03;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Aperçu du PDF — Devis #<?php echo htmlspecialchars((string)$devisId, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="muted">Cochez les pages à supprimer, puis générez le PDF final.</div>
            </div>
            <div class="grid">
                <div class="preview">
                    <!-- Aperçu inline du PDF courant -->
                    <iframe src="<?php echo htmlspecialchars($exportInlineUrl, ENT_QUOTES, 'UTF-8'); ?>" title="Aperçu PDF"></iframe>
                </div>
                <div class="panel">
                    <div class="panel-section">
                        <div class="row"><strong>Sélection des pages à supprimer</strong></div>
                        <div class="small muted">Indiquez les numéros de page à retirer, séparés par des virgules (ex: 2,4).</div>
                        <input type="text" id="deletePages" placeholder="ex: 2,4" value="<?php echo htmlspecialchars($initialDeletePages, ENT_QUOTES, 'UTF-8'); ?>" />
                        <div class="notice">Astuce: Si la 2ème page est vide (logo seul), entrez 2.</div>
                    </div>
                    <div class="panel-section">
                        <div class="row"><strong>Actions</strong></div>
                        <button class="btn primary btn-block" id="btnInline">Afficher inline</button>
                        <button class="btn success btn-block" id="btnDownload">Télécharger</button>
                        <button class="btn btn-block" id="btnRefresh">Rafraîchir l’aperçu</button>
                    </div>
                    <div class="panel-section">
                        <div class="small muted">La pagination (Page x/y) sera recalculée automatiquement après suppression.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const txt = document.getElementById('deletePages');
        const btnInline = document.getElementById('btnInline');
        const btnDownload = document.getElementById('btnDownload');
        const btnRefresh = document.getElementById('btnRefresh');

        function buildUrl(download) {
            const params = new URLSearchParams(window.location.search);
            const devisId = params.get('devisId');
            const del = (txt.value || '').trim();
            let url = 'export_pdf.php?devisId=' + encodeURIComponent(devisId || '') +
                (del ? ('&deletePages=' + encodeURIComponent(del)) : '');
            if (download) url += '&download=1';
            return url;
        }

        btnInline.addEventListener('click', () => {
            window.open(buildUrl(false), '_blank');
        });
        btnDownload.addEventListener('click', () => {
            window.open(buildUrl(true), '_blank');
        });
        btnRefresh.addEventListener('click', () => {
            const iframe = document.querySelector('.preview iframe');
            const base = 'export_pdf.php?devisId=' + new URLSearchParams(window.location.search).get('devisId');
            iframe.src = base; // recharger l’aperçu sans suppression
        });
    </script>
</body>

</html>