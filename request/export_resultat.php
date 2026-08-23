<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../fpdf186/fpdf.php';

use App\Domain\Quote\QuoteSearchRepository;

function pdf_text(mixed $value): string
{
    $text = (string) ($value ?? '');
    $converted = function_exists('iconv') ? @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) : false;
    return $converted === false ? $text : $converted;
}

final class QuoteListPdf extends FPDF
{
    public function Header(): void
    {
        $this->SetTextColor(34, 37, 75);
        $this->SetFont('Arial', 'B', 17);
        $this->Cell(0, 10, 'FIDEST - Liste des devis', 0, 1, 'L');
        $this->SetDrawColor(250, 189, 2);
        $this->SetLineWidth(1.2);
        $this->Line(10, 23, 287, 23);
        $this->Ln(7);
    }

    public function Footer(): void
    {
        $this->SetY(-13);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(90, 91, 112);
        $this->Cell(0, 8, pdf_text('Exporte le ' . date('d/m/Y H:i') . ' - Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }
}

$quotes = (new QuoteSearchRepository(app_database()))->search($_GET);
$pdf = new QuoteListPdf('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();
$pdf->SetFillColor(34, 37, 75);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 9);
$headers = [['Devis', 35], ['Client', 55], ['Destinataire', 55], ['Date', 28], ['Montant TTC', 42], ['Etat', 32], ['Emetteur', 30]];
foreach ($headers as [$label, $width]) $pdf->Cell($width, 9, pdf_text($label), 0, 0, 'L', true);
$pdf->Ln();
$pdf->SetTextColor(34, 37, 75);
$pdf->SetFont('Arial', '', 8);
$total = 0.0;
foreach ($quotes as $index => $quote) {
    $validated = !empty($quote['validation_commerciale']) && !empty($quote['validation_generale']);
    $expired = !empty($quote['date_expiration']) && $quote['date_expiration'] < date('Y-m-d');
    $status = $validated ? 'Valide' : ($expired ? 'Expire' : 'En cours');
    $fill = $index % 2 === 0;
    if ($fill) $pdf->SetFillColor(245, 246, 250);
    $values = [[$quote['numero_devis'], 35], [$quote['nom_client'] ?: '-', 55], [$quote['destine_a'] ?: '-', 55],
        [!empty($quote['date_emission']) ? date('d/m/Y', strtotime($quote['date_emission'])) : '-', 28],
        [number_format((float) $quote['total_ttc'], 0, ',', ' ') . ' FCFA', 42], [$status, 32], [$quote['emis_par'] ?: '-', 30]];
    foreach ($values as [$value, $width]) $pdf->Cell($width, 9, pdf_text(mb_strimwidth((string) $value, 0, 36, '...')), 0, 0, 'L', $fill);
    $pdf->Ln();
    $total += (float) $quote['total_ttc'];
}
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, pdf_text(count($quotes) . ' devis - Total TTC : ' . number_format($total, 0, ',', ' ') . ' FCFA'), 0, 1, 'R');
$pdf->Output('I', 'devis-fidest-' . date('Y-m-d') . '.pdf');
