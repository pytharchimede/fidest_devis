<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';

$devisId = (int) ($_GET['devisId'] ?? $_SESSION['devisId'] ?? 0);
if ($devisId <= 0) { http_response_code(400); exit('Identifiant du devis manquant.'); }
$statement = app_database()->prepare('SELECT numero_devis, destine_a, total_ttc FROM devis WHERE id = :id LIMIT 1');
$statement->execute(['id' => $devisId]);
$quote = $statement->fetch();
if (!$quote) { http_response_code(404); exit('Devis introuvable.'); }
$_SESSION['devisId'] = $devisId;
$deletePages = trim((string) ($_GET['deletePages'] ?? ''));
function p(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <base href="../">
    <title>Aperçu <?= p($quote['numero_devis']) ?> · FIDEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="branding.css.php">
    <style>
        body{min-height:100vh;background:var(--brand-background);color:var(--brand-text);font-family:var(--brand-font-body)}
        .preview-shell{width:min(1500px,calc(100% - 36px));margin:0 auto;padding:34px 0 54px}
        .preview-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:22px}
        .preview-heading h1{margin:0;color:var(--brand-primary);font:800 clamp(1.7rem,3vw,2.5rem)/1.1 var(--brand-font-heading)}
        .preview-heading p{margin:8px 0 0;color:var(--brand-text-muted)}
        .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--brand-primary);font-weight:800;text-decoration:none}
        .preview-workspace{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:20px}
        .pdf-stage{position:relative;overflow:hidden;height:calc(100vh - 245px);min-height:650px;background:#3c3e4d;border:1px solid var(--brand-border);border-radius:20px;box-shadow:0 18px 50px rgba(34,37,75,.17)}
        .pdf-stage iframe{width:100%;height:100%;border:0}.exact-badge{position:absolute;z-index:2;top:14px;left:14px;padding:7px 11px;color:#14733f;background:#dcf7e8;border-radius:99px;font-size:.7rem;font-weight:800;box-shadow:0 5px 18px rgba(0,0,0,.12)}
        .preview-sidebar{display:flex;flex-direction:column;gap:14px}.side-card{padding:20px;background:#fff;border:1px solid var(--brand-border);border-radius:17px;box-shadow:0 10px 28px rgba(34,37,75,.07)}
        .side-card h2{margin:0 0 14px;color:var(--brand-primary);font:800 1rem var(--brand-font-heading)}.quote-meta{display:grid;gap:11px}.quote-meta div{padding-bottom:11px;border-bottom:1px solid var(--brand-border)}.quote-meta small{display:block;color:var(--brand-text-muted);font-size:.68rem;text-transform:uppercase}.quote-meta strong{display:block;margin-top:3px;color:var(--brand-text)}
        .page-field{width:100%;padding:11px 12px;border:1px solid var(--brand-border);border-radius:10px;outline:0}.page-field:focus{border-color:var(--brand-accent);box-shadow:0 0 0 4px rgba(250,189,2,.15)}
        .action-stack{display:grid;gap:9px}.action-stack .btn{display:flex;align-items:center;justify-content:center;gap:8px;min-height:44px;border-radius:10px;font-weight:800}.btn-download{color:#22254b;background:var(--brand-accent);border-color:var(--brand-accent)}.btn-open{color:#fff;background:var(--brand-primary);border-color:var(--brand-primary)}
        .helper{margin-top:8px;color:var(--brand-text-muted);font-size:.74rem;line-height:1.45}
        @media(max-width:1000px){.preview-workspace{grid-template-columns:1fr}.pdf-stage{height:72vh}.preview-sidebar{display:grid;grid-template-columns:1fr 1fr}.preview-heading{align-items:flex-start;flex-direction:column}}@media(max-width:650px){.preview-sidebar{grid-template-columns:1fr}.preview-shell{width:calc(100% - 22px)}.pdf-stage{min-height:520px}}
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/navbar.php'; ?>
<main class="preview-shell">
    <header class="preview-heading"><div><a class="back-link" href="liste_devis.php"><i class="fa-solid fa-arrow-left"></i> Retour aux devis</a><h1>Aperçu final du devis</h1><p>Le document affiché est exactement celui produit lors du téléchargement.</p></div><a class="btn btn-outline-secondary" href="modifier_devis.php?devisId=<?= $devisId ?>"><i class="fa-solid fa-pen-to-square"></i> Revenir à l’édition</a></header>
    <div class="preview-workspace">
        <section class="pdf-stage"><span class="exact-badge"><i class="fa-solid fa-circle-check"></i> Rendu PDF exact</span><iframe id="pdfFrame" src="request/export_pdf.php?devisId=<?= $devisId ?><?= $deletePages !== '' ? '&amp;deletePages=' . urlencode($deletePages) : '' ?>" title="Aperçu PDF <?= p($quote['numero_devis']) ?>"></iframe></section>
        <aside class="preview-sidebar">
            <section class="side-card"><h2><i class="fa-regular fa-file-lines"></i> Informations</h2><div class="quote-meta"><div><small>Référence</small><strong><?= p($quote['numero_devis']) ?></strong></div><div><small>Destinataire</small><strong><?= p(strtok((string)$quote['destine_a'], "\n") ?: 'Non renseigné') ?></strong></div><div><small>Montant TTC</small><strong><?= number_format((float)$quote['total_ttc'],0,',',' ') ?> FCFA</strong></div></div></section>
            <section class="side-card"><h2><i class="fa-solid fa-layer-group"></i> Gestion des pages</h2><label class="form-label" for="deletePages">Pages à retirer</label><input class="page-field" id="deletePages" value="<?= p($deletePages) ?>" placeholder="Ex. 2, 4"><div class="helper">Indiquez les numéros séparés par des virgules. L’aperçu et le téléchargement utiliseront la même sélection.</div></section>
            <section class="side-card"><h2><i class="fa-solid fa-bolt"></i> Actions</h2><div class="action-stack"><button class="btn btn-open" id="refresh"><i class="fa-solid fa-rotate"></i> Actualiser l’aperçu</button><button class="btn btn-download" id="download"><i class="fa-solid fa-file-arrow-down"></i> Télécharger le PDF</button><button class="btn btn-light" id="open"><i class="fa-solid fa-up-right-from-square"></i> Ouvrir dans un onglet</button></div></section>
        </aside>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const field=document.getElementById('deletePages'),frame=document.getElementById('pdfFrame');
function pdfUrl(download=false){const pages=field.value.trim();return 'request/export_pdf.php?devisId=<?= $devisId ?>'+(pages?'&deletePages='+encodeURIComponent(pages):'')+(download?'&download=1':'');}
document.getElementById('refresh').onclick=()=>frame.src=pdfUrl()+'&refresh='+Date.now();
document.getElementById('download').onclick=()=>window.location.href=pdfUrl(true);
document.getElementById('open').onclick=()=>window.open(pdfUrl(),'_blank');
field.addEventListener('keydown',event=>{if(event.key==='Enter')document.getElementById('refresh').click()});
</script>
</body></html>
