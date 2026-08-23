<?php

declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../fpdf186/fpdf.php';
function profilePdfText(mixed $value): string
{
    $text = (string) ($value ?? '');
    $converted = function_exists('iconv') ? @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) : false;
    return $converted === false ? $text : $converted;
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Profil invalide.');
}
$db = app_database();
$stmt = $db->prepare('SELECT * FROM user_devis WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit('Profil introuvable.');
}
$kpis = ['quotes' => 0, 'published' => 0, 'validated' => 0, 'total_ttc' => 0.0];
try {
    $kpi = $db->prepare('SELECT COUNT(*) AS quotes, COALESCE(SUM(publier_devis), 0) AS published, COALESCE(SUM(validation_generale), 0) AS validated, COALESCE(SUM(total_ttc), 0) AS total_ttc FROM devis WHERE created_by_user_id = :id');
    $kpi->execute(['id' => $id]);
    $kpis = array_merge($kpis, $kpi->fetch(PDO::FETCH_ASSOC) ?: []);
} catch (PDOException $exception) {
    // Les anciennes installations sans created_by_user_id conservent l’export du profil.
}
final class ProfilePdf extends FPDF
{
    public function Header(): void
    {
        $logo = APP_ROOT . '/img/logo_fidest.png';
        if (is_file($logo)) $this->Image($logo, 12, 9, 34);
        $this->SetXY(52, 11);
        $this->SetTextColor(34, 37, 75);
        $this->SetFont('Arial', 'B', 17);
        $this->Cell(0, 8, 'PROFIL COLLABORATEUR', 0, 1);
        $this->SetX(52);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(107, 110, 130);
        $this->Cell(0, 5, profilePdfText('Fiche membre et activité commerciale'), 0, 1);
        $this->SetDrawColor(250, 189, 2);
        $this->SetLineWidth(1.2);
        $this->Line(12, 31, 285, 31);
        $this->Ln(14);
    }
    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(107, 110, 130);
        $this->Cell(0, 4, profilePdfText(app_branding()->footerBlock()), 0, 1, 'C');
        $this->Cell(0, 4, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}
$name = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
$pdf = new ProfilePdf('P', 'mm', 'A4');
$pdf->SetMargins(18, 12, 18);
$pdf->AddPage();
$pdf->SetTextColor(34, 37, 75);
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 12, profilePdfText($name), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(107, 110, 130);
$pdf->Cell(0, 6, profilePdfText((string) ($user['fonction'] ?? 'Collaborateur FIDEST')), 0, 1);
$pdf->Cell(0, 6, profilePdfText((string) ($user['mail_pro'] ?? '')), 0, 1);
$pdf->Ln(8);
$pdf->SetFillColor(34, 37, 75);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(174, 9, 'INDICATEURS D’ACTIVITE', 0, 1, 'L', true);
$pdf->SetTextColor(34, 36, 58);
$pdf->SetFont('Arial', '', 10);
$metrics = [['Devis édités', (int) $kpis['quotes']], ['Devis publiés', (int) $kpis['published']], ['Devis validés', (int) $kpis['validated']], ['Montant TTC cumulé', number_format((float) $kpis['total_ttc'], 0, ',', ' ') . ' FCFA']];
foreach ($metrics as [$label, $value]) {
    $pdf->SetFillColor(246, 246, 249);
    $pdf->Cell(112, 11, profilePdfText($label), 1, 0, 'L', true);
    $pdf->Cell(62, 11, profilePdfText((string) $value), 1, 1, 'R', true);
}
$pdf->Ln(9);
$pdf->SetFillColor(34, 37, 75);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(174, 9, 'INFORMATIONS PROFESSIONNELLES', 0, 1, 'L', true);
$pdf->SetTextColor(34, 36, 58);
$pdf->SetFont('Arial', '', 10);
foreach ([['Téléphone', $user['telephone'] ?? 'Non renseigné'], ['Département', $user['departement'] ?? 'Non renseigné'], ['Adresse', $user['adresse'] ?? 'Non renseignée'], ['Statut', !empty($user['active']) ? 'Actif' : 'Désactivé']] as [$label, $value]) {
    $pdf->Cell(45, 9, profilePdfText($label), 0, 0, 'L');
    $pdf->Cell(129, 9, profilePdfText((string) $value), 0, 1, 'L');
}
$pdf->Ln(6);
$pdf->SetTextColor(107, 110, 130);
$pdf->SetFont('Arial', 'I', 8);
$pdf->MultiCell(174, 5, profilePdfText('Document généré le ' . date('d/m/Y à H:i') . ' depuis l’espace FIDEST.'), 0, 'L');
$pdf->Output('I', 'profil-' . preg_replace('/[^a-z0-9-]+/i', '-', strtolower($name)) . '.pdf');
