<?php

declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../fpdf186/fpdf.php';

function userPdfText(mixed $value): string
{
    $text = (string) ($value ?? '');
    $converted = function_exists('iconv') ? @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) : false;
    return $converted === false ? $text : $converted;
}
$users = app_database()->query('SELECT mail_pro, nom, prenom, modifier_devis, visualiser_devis, soumettre_devis, masquer_devis, envoyer_devis, valider_devis, active, created_at FROM user_devis ORDER BY nom, prenom')->fetchAll(PDO::FETCH_ASSOC);
final class UsersPdf extends FPDF
{
    public function Header(): void
    {
        $logo = APP_ROOT . '/img/logo_fidest.png';
        if (is_file($logo)) $this->Image($logo, 12, 9, 34);
        $this->SetXY(52, 11);
        $this->SetTextColor(34, 37, 75);
        $this->SetFont('Arial', 'B', 17);
        $this->Cell(0, 8, 'EQUIPE FIDEST', 0, 1);
        $this->SetX(52);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(107, 110, 130);
        $this->Cell(0, 5, userPdfText('Membres, statuts et droits d’accès'), 0, 1);
        $this->SetDrawColor(250, 189, 2);
        $this->SetLineWidth(1.2);
        $this->Line(12, 31, 285, 31);
        $this->Ln(12);
    }
    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(107, 110, 130);
        $this->Cell(0, 4, userPdfText(app_branding()->footerBlock()), 0, 1, 'C');
        $this->Cell(0, 4, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}
$pdf = new UsersPdf('L', 'mm', 'A4');
$pdf->SetMargins(12, 10, 12);
$pdf->AddPage();
$pdf->SetFillColor(34, 37, 75);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 8);
foreach ([['Prénom', 30], ['Nom', 34], ['Email', 63], ['Statut', 25], ['Créé le', 28], ['Droits actifs', 82]] as [$label, $width]) $pdf->Cell($width, 9, userPdfText($label), 0, 0, 'L', true);
$pdf->Ln();
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(34, 36, 58);
foreach ($users as $index => $user) {
    if ($index % 2 === 0) $pdf->SetFillColor(246, 246, 249);
    $rights = [];
    foreach (['modifier_devis' => 'Modifier', 'visualiser_devis' => 'Visualiser', 'soumettre_devis' => 'Soumettre', 'masquer_devis' => 'Masquer', 'envoyer_devis' => 'Envoyer', 'valider_devis' => 'Valider'] as $key => $label) if ((int) $user[$key] === 1) $rights[] = $label;
    foreach ([[$user['prenom'], 30], [$user['nom'], 34], [$user['mail_pro'], 63], [$user['active'] ? 'Actif' : 'Désactivé', 25], [date('d/m/Y', strtotime($user['created_at'])), 28], [implode(', ', $rights) ?: 'Aucun', 82]] as [$value, $width]) $pdf->Cell($width, 9, userPdfText(mb_strimwidth((string) $value, 0, 45, '...')), 0, 0, 'L', $index % 2 === 0);
    $pdf->Ln();
}
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(34, 37, 75);
$pdf->Cell(0, 8, userPdfText(count($users) . ' membre(s)'), 0, 1, 'R');
$pdf->Output('I', 'equipe-fidest-' . date('Y-m-d') . '.pdf');
