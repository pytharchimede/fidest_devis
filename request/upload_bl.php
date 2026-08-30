<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';

function loadRegistry(string $path): array
{
    if (!is_file($path)) return ['items' => []];
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : ['items' => []];
}
function pageEscape(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

$registryPath = APP_ROOT . '/data/bl_registry.json';
$uploadDir = APP_ROOT . '/bl_signed';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $devisId = (int) ($_POST['devisId'] ?? 0);
    $file = $_FILES['bl_file'] ?? null;
    if ($devisId <= 0 || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        header('Location: upload_bl.php?devisId=' . $devisId . '&error=missing'); exit;
    }
    if ((int) $file['size'] > 10 * 1024 * 1024) {
        header('Location: upload_bl.php?devisId=' . $devisId . '&error=size'); exit;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
    if (!isset($extensions[$mime])) {
        header('Location: upload_bl.php?devisId=' . $devisId . '&error=format'); exit;
    }
    $destName = 'bl_signed_' . $devisId . '_' . date('Ymd_His') . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $destName)) {
        header('Location: upload_bl.php?devisId=' . $devisId . '&error=upload'); exit;
    }
    $registry = loadRegistry($registryPath);
    $registry['items'] = array_values(array_filter($registry['items'], static fn(array $item): bool => (int) $item['devisId'] !== $devisId));
    $registry['items'][] = ['devisId'=>$devisId,'file'=>'bl_signed/'.$destName,'signedAt'=>date(DATE_ATOM)];
    file_put_contents($registryPath, json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    $_SESSION['bl_signed_' . $devisId] = true;
    header('Location: ../liste_devis.php?uploaded_bl=1'); exit;
}

$devisId = (int) ($_GET['devisId'] ?? 0);
$database = app_database();
$details = null;
if ($devisId > 0) {
    $statement = $database->prepare('SELECT d.id,d.numero_devis,d.date_emission,d.date_expiration,d.total_ttc,d.destine_a,c.nom_client AS client_nom FROM devis d LEFT JOIN client c ON c.id_client=d.client_id WHERE d.id=:id AND d.archived_at IS NULL');
    $statement->execute(['id'=>$devisId]);
    $details = $statement->fetch() ?: null;
    if (!$details) { http_response_code(404); exit('Devis introuvable.'); }
}
$devisList = $devisId > 0 ? [] : $database->query('SELECT d.id,d.numero_devis,c.nom_client AS client_nom FROM devis d LEFT JOIN client c ON c.id_client=d.client_id WHERE d.masque=0 AND d.archived_at IS NULL ORDER BY d.id DESC LIMIT 500')->fetchAll();
$currentDocument = null;
foreach (loadRegistry($registryPath)['items'] as $item) if ((int)$item['devisId'] === $devisId) $currentDocument = $item;
$errors = ['missing'=>'Sélectionnez un document avant de continuer.','size'=>'Le fichier dépasse la limite de 10 Mo.','format'=>'Format refusé. Utilisez un PDF, JPG ou PNG.','upload'=>'Le transfert a échoué. Veuillez réessayer.'];
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="../"><title>Bon de livraison signé · FIDEST</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="branding.css.php"><link rel="stylesheet" href="css/smart-select.css"><style>
body{min-height:100vh;background:var(--brand-background);color:var(--brand-text);font-family:var(--brand-font-body)}.upload-shell{width:min(1180px,calc(100% - 36px));margin:0 auto;padding:38px 0 65px}.upload-heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:24px}.upload-heading h1{margin:7px 0 0;color:var(--brand-primary);font:800 clamp(1.8rem,3vw,2.8rem)/1.05 var(--brand-font-heading)}.upload-heading p{margin:8px 0 0;color:var(--brand-text-muted)}.back-link{color:var(--brand-primary);font-weight:800;text-decoration:none}.upload-layout{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(300px,.7fr);gap:20px}.upload-card,.context-card{padding:26px;background:#fff;border:1px solid var(--brand-border);border-radius:20px;box-shadow:0 14px 40px rgba(34,37,75,.08)}.context-card{align-self:start}.context-card h2,.upload-card h2{margin:0 0 18px;color:var(--brand-primary);font:800 1.08rem var(--brand-font-heading)}.dropzone{position:relative;display:grid;min-height:330px;place-items:center;padding:34px;text-align:center;background:linear-gradient(145deg,#fafafd,#f3f3f8);border:2px dashed #b8bac9;border-radius:18px;cursor:pointer;transition:.22s}.dropzone:hover,.dropzone.dragover{background:#fff9e6;border-color:var(--brand-accent);transform:translateY(-2px)}.drop-icon{display:grid;width:78px;height:78px;margin:0 auto 18px;place-items:center;color:var(--brand-primary);background:#e8e9f1;border-radius:22px;font-size:1.8rem}.dropzone strong{display:block;color:var(--brand-primary);font-size:1.05rem}.dropzone small{display:block;margin-top:8px;color:var(--brand-text-muted)}.file-card{display:none;grid-template-columns:52px 1fr auto;gap:12px;align-items:center;margin-top:14px;padding:14px;background:#f7f7fa;border:1px solid var(--brand-border);border-radius:13px}.file-card.visible{display:grid}.file-card__icon{display:grid;width:48px;height:48px;place-items:center;color:#a62c37;background:#fbe9eb;border-radius:12px}.file-card small{display:block;color:var(--brand-text-muted)}.doc-preview{display:none;max-height:290px;margin-top:14px;overflow:hidden;border:1px solid var(--brand-border);border-radius:14px}.doc-preview.visible{display:block}.doc-preview img{width:100%;max-height:290px;object-fit:contain;background:#eee}.upload-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}.upload-actions .btn{display:inline-flex;align-items:center;gap:8px;min-height:46px;padding:0 18px;border-radius:11px;font-weight:800}.btn-submit{color:var(--brand-primary);background:var(--brand-accent);border-color:var(--brand-accent)}.quote-summary{display:grid;gap:14px}.quote-summary div{padding-bottom:13px;border-bottom:1px solid var(--brand-border)}.quote-summary small{display:block;color:var(--brand-text-muted);font-size:.66rem;font-weight:800;text-transform:uppercase}.quote-summary strong{display:block;margin-top:4px;color:var(--brand-text)}.process{display:grid;gap:12px;margin-top:22px}.process-step{display:grid;grid-template-columns:32px 1fr;gap:10px;align-items:start}.process-step span{display:grid;width:30px;height:30px;place-items:center;color:var(--brand-primary);background:#ececf3;border-radius:9px;font-size:.72rem;font-weight:900}.process-step strong,.process-step small{display:block}.process-step small{color:var(--brand-text-muted);font-size:.72rem}.existing-doc{margin-top:18px;padding:13px;color:#14733f;background:#dcf7e8;border-radius:12px}.selector-card{max-width:760px;margin:auto;padding:26px;background:#fff;border:1px solid var(--brand-border);border-radius:20px}@media(max-width:850px){.upload-layout{grid-template-columns:1fr}.upload-heading{align-items:flex-start;flex-direction:column}.dropzone{min-height:270px}}
</style></head><body><?php include __DIR__.'/../partials/navbar.php'; ?><main class="upload-shell"><header class="upload-heading"><div><a class="back-link" href="liste_devis.php"><i class="fa-solid fa-arrow-left"></i> Retour aux devis</a><h1>Déposer un BL signé</h1><p>Centralisez la preuve de livraison et verrouillez le document commercial.</p></div><?php if($details):?><a class="btn btn-outline-secondary" target="_blank" href="export_bl.php?devisId=<?=$devisId?>"><i class="fa-solid fa-file-arrow-down"></i> Voir le BL original</a><?php endif;?></header>
<?php if(isset($_GET['error'],$errors[$_GET['error']])):?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?=pageEscape($errors[$_GET['error']])?></div><?php endif;?>
<?php if(!$details):?><section class="selector-card"><h2 class="h4">Choisir le devis concerné</h2><p class="text-muted">Recherchez par numéro ou client.</p><form method="get" class="d-grid gap-3"><select name="devisId" required data-smart-select data-placeholder="Rechercher un devis ou un client…"><option value="">Choisir</option><?php foreach($devisList as $quote):?><option value="<?=$quote['id']?>"><?=pageEscape($quote['numero_devis'].' — '.($quote['client_nom']?:'Client non renseigné'))?></option><?php endforeach;?></select><button class="btn btn-primary">Continuer <i class="fa-solid fa-arrow-right"></i></button></form></section><?php else:?>
<div class="upload-layout"><section class="upload-card"><h2><i class="fa-solid fa-cloud-arrow-up"></i> Document signé</h2><form method="post" enctype="multipart/form-data" id="uploadForm"><input type="hidden" name="devisId" value="<?=$devisId?>"><div class="dropzone" id="dropzone"><div><span class="drop-icon"><i class="fa-solid fa-file-signature"></i></span><strong>Glissez le BL signé dans cette zone</strong><small>ou cliquez pour choisir un fichier · PDF, JPG ou PNG · 10 Mo maximum</small></div><input hidden type="file" name="bl_file" id="fileInput" accept="application/pdf,image/jpeg,image/png" required></div><div class="file-card" id="fileCard"><span class="file-card__icon"><i class="fa-solid fa-file-pdf"></i></span><div><strong id="fileName"></strong><small id="fileSize"></small></div><button class="btn btn-light" type="button" id="removeFile"><i class="fa-solid fa-xmark"></i></button></div><div class="doc-preview" id="preview"></div><div class="upload-actions"><button class="btn btn-submit" type="submit" id="submitButton" disabled><i class="fa-solid fa-shield-check"></i> Enregistrer le BL signé</button><a class="btn btn-light" href="liste_devis.php">Annuler</a></div></form></section>
<aside class="context-card"><h2>Contexte du devis</h2><div class="quote-summary"><div><small>Référence</small><strong><?=pageEscape($details['numero_devis'])?></strong></div><div><small>Client</small><strong><?=pageEscape($details['client_nom']?:strtok((string)$details['destine_a'],"\n"))?></strong></div><div><small>Date d’émission</small><strong><?=pageEscape($details['date_emission'])?></strong></div><div><small>Montant TTC</small><strong><?=number_format((float)$details['total_ttc'],0,',',' ')?> FCFA</strong></div></div><?php if($currentDocument):?><div class="existing-doc"><i class="fa-solid fa-circle-check"></i> Un BL signé existe déjà. Le nouveau fichier le remplacera.</div><?php endif;?><div class="process"><div class="process-step"><span>1</span><div><strong>Vérifiez le devis</strong><small>Confirmez la référence et le client.</small></div></div><div class="process-step"><span>2</span><div><strong>Déposez la preuve</strong><small>Utilisez le document signé et lisible.</small></div></div><div class="process-step"><span>3</span><div><strong>Verrouillage</strong><small>Le devis ne sera plus modifiable depuis le listing.</small></div></div></div></aside></div><?php endif;?></main>
<script src="js/smart-select.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script><?php if($details):?><script>
const zone=document.getElementById('dropzone'),input=document.getElementById('fileInput'),card=document.getElementById('fileCard'),preview=document.getElementById('preview'),submit=document.getElementById('submitButton');
function selectFile(file){if(!file)return;if(file.size>10*1024*1024){alert('Le fichier dépasse 10 Mo.');return}input.files=(()=>{const dt=new DataTransfer();dt.items.add(file);return dt.files})();document.getElementById('fileName').textContent=file.name;document.getElementById('fileSize').textContent=(file.size/1024/1024).toFixed(2)+' Mo';card.classList.add('visible');submit.disabled=false;preview.innerHTML='';preview.classList.remove('visible');if(file.type.startsWith('image/')){const img=document.createElement('img');img.src=URL.createObjectURL(file);preview.append(img);preview.classList.add('visible')}}
zone.onclick=()=>input.click();zone.ondragover=e=>{e.preventDefault();zone.classList.add('dragover')};zone.ondragleave=()=>zone.classList.remove('dragover');zone.ondrop=e=>{e.preventDefault();zone.classList.remove('dragover');selectFile(e.dataTransfer.files[0])};input.onchange=()=>selectFile(input.files[0]);document.getElementById('removeFile').onclick=()=>{input.value='';card.classList.remove('visible');preview.classList.remove('visible');submit.disabled=true};
</script><?php endif;?></body></html>
