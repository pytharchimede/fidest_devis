<?php
session_start();

require_once('../fpdf186/fpdf.php');
require_once('../model/Database.php');
require_once('../model/User.php');
require_once('../model/Devis.php');
require_once('../phpqrcode/qrlib.php');

$pdo = \Database::getConnection();
$con = $pdo;
$userObj = new User($pdo);
$devisObj = new Devis($pdo);

// Vérifier le devisId via GET (devisId ou id) ou session
if (!isset($_SESSION['devisId']) && !isset($_GET['devisId']) && !isset($_GET['id'])) {
    die('ID de devis non défini.');
}
$devisId = null;
if (isset($_GET['devisId'])) {
    $devisId = (int)$_GET['devisId'];
} elseif (isset($_GET['id'])) {
    $devisId = (int)$_GET['id'];
} else {
    $devisId = (int)$_SESSION['devisId'];
}
$_SESSION['devisId'] = $devisId;

// Charger devis, lignes, client
$stmt = $con->prepare('SELECT * FROM devis WHERE id = ?');
$stmt->execute([$devisId]);
$devis = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$devis) die('Devis non trouvé.');

$stmt = $con->prepare('SELECT * FROM ligne_devis WHERE devis_id = ?');
$stmt->execute([$devisId]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmt = $con->prepare('SELECT * FROM client WHERE id_client = ?');
$stmt->execute([$devis['client_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$client) die('Client non trouvé.');

class PDF_BL extends FPDF
{
    public $fidestLogoPath = null;
    public $veritasLogoPath = null;
    public $qrPath = null;
    function Header()
    {
        $this->SetFont('Arial', 'B', 12);
        // Logos et QR
        // Logo FIDEST à gauche si dispo
        if ($this->fidestLogoPath && file_exists($this->fidestLogoPath)) {
            $this->Image($this->fidestLogoPath, 10, 10, 40);
        }
        // Veritas à droite si dispo
        if ($this->veritasLogoPath && file_exists($this->veritasLogoPath)) {
            $this->Image($this->veritasLogoPath, 150, 10, 30);
        }
        // QR code en haut-droite
        if ($this->qrPath && file_exists($this->qrPath)) {
            $this->Image($this->qrPath, 180, 10, 20);
        }
        $this->Ln(20);
    }
    function Footer()
    {
        $this->SetDrawColor(0, 0, 0);
        $this->Line(10, 272, 200, 272);
        $this->SetY(-22);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 3.5, pdf_text("FOURNITURES INDUSTRIELLES, DEPANNAGE ET TRAVAUX PUBLIQUES - Au capital de 10 000 000 F CFA - Siège Social : Abidjan, Koumassi, Zone industrielle"), 0, 1, 'C');
        $this->Cell(0, 3.5, pdf_text("01 BP 1642 Abidjan 01 - Téléphone : (+225) +225 27-21-36-27-27  -  Email : info@fidest.org - RCCM : CI-ABJ-2017-B-20163  -  N° CC : 010274200088"), 0, 1, 'C');
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    function removePage($pageNo)
    {
        if (!isset($this->pages) || $this->page < 2) return;
        $pageNo = (int)$pageNo;
        if ($pageNo < 1 || $pageNo > $this->page) return;
        $newPages = [];
        $newIndex = 1;
        for ($i = 1; $i <= $this->page; $i++) {
            if ($i === $pageNo) continue;
            $newPages[$newIndex++] = $this->pages[$i];
        }
        $this->pages = [];
        $this->page = count($newPages);
        for ($i = 1; $i <= $this->page; $i++) {
            $this->pages[$i] = $newPages[$i];
        }
    }
}

// Helpers
function pdf_text($s)
{
    if ($s === null) return '';
    $s = (string)$s;
    // Convertir UTF-8 vers Windows-1252 (proche ISO-8859-1) avec translittération
    $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    return ($converted === false) ? $s : $converted;
}
function label_val($label, $val)
{
    return [$label, $val];
}

$pdf = new PDF_BL('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->AddFont('BookAntiqua', '', 'bookantiqua.php');
$pdf->AddFont('BookAntiqua', 'B', 'bookantiqua_bold.php');
$pdf->SetFont('BookAntiqua', '', 12);

// Préparer logos et QR
// Logo FIDEST (à gauche) depuis devis ou dossier img/logo
$fidestCandidates = [];
if (!empty($devis['logo'])) {
    $fidestCandidates[] = __DIR__ . '/../logo/' . $devis['logo'];
    $fidestCandidates[] = __DIR__ . '/../../img/' . $devis['logo'];
}
// fallback connu
$fidestCandidates[] = __DIR__ . '/../logo/logo_fidest.jpg';
$fidestCandidates[] = __DIR__ . '/../../img/logo_fidest.jpg';
foreach ($fidestCandidates as $p) {
    if (file_exists($p)) {
        $pdf->fidestLogoPath = $p;
        break;
    }
}

// Logo Veritas (à droite) fallback
$veritasCandidates = [__DIR__ . '/../logo/logo_veritas.jpg', __DIR__ . '/../../img/logo_veritas.jpg'];
foreach ($veritasCandidates as $p) {
    if (file_exists($p)) {
        $pdf->veritasLogoPath = $p;
        break;
    }
}

// Générer le QR code pour BL
$qrData = 'https://fidest.ci/devis/request/export_bl.php?devisId=' . $devis['id'];
$qrFile = __DIR__ . '/../qrCodeFile/qrcode_bl_' . $devis['id'] . '.png';
QRcode::png($qrData, $qrFile, 'L', 4, 2);
$pdf->qrPath = $qrFile;

// Titre BL
$pdf->SetFont('BookAntiqua', 'B', 14);
$pdf->Cell(0, 10, pdf_text('Bon de Livraison'), 0, 1, 'L');
$pdf->SetFont('BookAntiqua', '', 10);
$pdf->Cell(0, 6, pdf_text('BL relatif au Devis N° ' . $devis['numero_devis']), 0, 1, 'L');
$pdf->Ln(2);

// Bloc client & infos BL
$pdf->SetFont('BookAntiqua', 'B', 10);
$pdf->Cell(0, 6, pdf_text(strtoupper($client['nom_client'])), 0, 1, 'L');
$pdf->SetFont('BookAntiqua', '', 8);
$pdf->Cell(0, 5, pdf_text($client['localisation_client']), 0, 1, 'L');
$pdf->Cell(0, 5, pdf_text(trim(($client['commune_client'] ?? '') . ' ' . ($client['bp_client'] ?? ''))), 0, 1, 'L');
$pdf->Cell(0, 5, pdf_text($client['pays_client']), 0, 1, 'L');

// Méta BL à droite
$x = 135;
$y = 50;
$w = 65;
$h = 5;
$pdf->SetFont('BookAntiqua', 'B', 10);
$pdf->SetXY($x, $y);
$pdf->MultiCell($w, $h, pdf_text('Date: ' . date('d/m/Y')), 0, 'L');
$y = $pdf->GetY();
$pdf->SetXY($x, $y);
$pdf->MultiCell($w, $h, pdf_text('Référence: BL-' . $devis['id']), 0, 'L');
$y = $pdf->GetY();
$pdf->SetXY($x, $y);
$pdf->MultiCell($w, $h, pdf_text("Destinataire: " . $devis['correspondant']), 0, 'L');

$pdf->Ln(6);

// En-tête du tableau
$pdf->SetFont('BookAntiqua', 'B', 8);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(169, 169, 169);
$pdf->Cell(10, 10, pdf_text('Pos.'), 1, 0, 'C', true);
$pdf->Cell(105, 10, pdf_text('Description'), 1, 0, 'C', true);
$pdf->Cell(25, 10, pdf_text('Quantité'), 1, 0, 'C', true);
$pdf->Cell(50, 10, pdf_text('Observations'), 1, 0, 'C', true);
$pdf->Ln();

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetDrawColor(220, 220, 220);
$pdf->SetFont('BookAntiqua', '', 8);

$ligneHauteur = 10; // hauteur base
$margeBas = 20;
$pageHauteurMax = 297 - $margeBas;
$blocSignHauteur = 70; // espace requis pour signatures

foreach ($lignes as $i => $ligne) {
    $resteBloc = ($i == count($lignes) - 1) ? $blocSignHauteur : 0;
    if ($pdf->GetY() + $ligneHauteur + $resteBloc > $pageHauteurMax) {
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 8, pdf_text('Le tableau se poursuit à la page suivante...'), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->AddPage();
        // Réafficher l'entête
        $pdf->SetFont('BookAntiqua', 'B', 8);
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(169, 169, 169);
        $pdf->Cell(10, 10, pdf_text('Pos.'), 1, 0, 'C', true);
        $pdf->Cell(105, 10, pdf_text('Description'), 1, 0, 'C', true);
        $pdf->Cell(25, 10, pdf_text('Quantité'), 1, 0, 'C', true);
        $pdf->Cell(50, 10, pdf_text('Observations'), 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetFont('BookAntiqua', '', 8);
    }

    $xStart = $pdf->GetX();
    $yStart = $pdf->GetY();
    // Pos
    $pdf->Cell(10, 10, $i + 1, 1);
    // Description en MultiCell bordures LR + trait bas unique
    $pdf->SetFont('BookAntiqua', 'B', 8);
    $pdf->SetXY($xStart + 10, $yStart);
    $pdf->MultiCell(105, 5, pdf_text($ligne['designation']), 'LR', 'L');
    $descHeight = $pdf->GetY() - $yStart;
    $rowHeight = max($descHeight, 10);
    $pdf->SetXY($xStart + 10, $yStart + $rowHeight);
    $pdf->Cell(105, 0, '', 'T');

    // Autres colonnes alignées sur la hauteur de ligne
    $pdf->SetFont('BookAntiqua', '', 8);
    $pdf->SetXY($xStart + 10 + 105, $yStart);
    $pdf->Cell(25, $rowHeight, $ligne['quantite'], 1, 0, 'C');
    $pdf->Cell(50, $rowHeight, '', 1, 0, 'L'); // Observations vide (à remplir à la main)

    $pdf->Ln($rowHeight);
}

// Signatures & cachets
if ($pdf->GetY() + $blocSignHauteur > $pageHauteurMax) {
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 8, pdf_text('Le tableau se poursuit à la page suivante...'), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->AddPage();
}

$pdf->Ln(5);
$pdf->SetDrawColor(0, 0, 0);
$pdf->Cell(190, 0, '', 'T');
$pdf->Ln(6);

$pdf->SetFont('BookAntiqua', 'B', 10);
$pdf->Cell(95, 8, pdf_text('Cachet & Signature du Client'), 0, 0, 'L');
$pdf->Cell(95, 8, pdf_text('Signature FIDEST'), 0, 1, 'R');

$pdf->Ln(2);
$pdf->Cell(95, 35, '', 1, 0, 'C');
$pdf->Cell(0, 35, '', 1, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('BookAntiqua', '', 9);
$pdf->Cell(95, 6, pdf_text('Nom du Réceptionnaire: __________________________'), 0, 0, 'L');
$pdf->Cell(0, 6, pdf_text('Nom et Qualité: ________________________________'), 0, 1, 'R');
$pdf->Cell(95, 6, pdf_text('Date & Heure: ____/____/________  ____:____'), 0, 0, 'L');
$pdf->Cell(0, 6, pdf_text('Date: ____/____/________'), 0, 1, 'R');

// Sortie
if (ob_get_length()) ob_clean();
header('Content-Type: application/pdf');
if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Disposition: attachment; filename="bl_' . $devis['id'] . '.pdf"');
} else {
    header('Content-Disposition: inline; filename="bl_' . $devis['id'] . '.pdf"');
}

// Suppression de pages si demandé
if (isset($_GET['deletePages'])) {
    $list = explode(',', $_GET['deletePages']);
    $numbers = array_map('intval', $list);
    rsort($numbers);
    foreach ($numbers as $pno) {
        if ($pno > 0) {
            $pdf->removePage($pno);
        }
    }
}

if (isset($_GET['download']) && $_GET['download'] == '1') {
    $pdf->Output('D', 'bl_' . $devis['id'] . '.pdf');
} else {
    $pdf->Output('I', 'bl_' . $devis['id'] . '.pdf');
}

unset($_SESSION['devisId']);
exit;
