<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../fpdf186/fpdf.php';
use App\Domain\PurchaseOrder\PurchaseOrderRepository;
function orderPdfText(mixed $value): string { $text = (string) ($value ?? ''); $converted = function_exists('iconv') ? @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) : false; return $converted === false ? $text : $converted; }
final class PurchaseOrdersPdf extends FPDF {
    public function Header(): void { $logo = APP_ROOT . '/img/logo_fidest.png'; if (is_file($logo)) $this->Image($logo, 12, 9, 34); $this->SetXY(52, 11); $this->SetTextColor(34, 37, 75); $this->SetFont('Arial', 'B', 17); $this->Cell(0, 8, 'BONS DE COMMANDE', 0, 1); $this->SetX(52); $this->SetFont('Arial', '', 8); $this->SetTextColor(107, 110, 130); $this->Cell(0, 5, orderPdfText('Recapitulatif des commandes clients recues'), 0, 1); $this->SetDrawColor(250, 189, 2); $this->SetLineWidth(1.2); $this->Line(12, 31, 285, 31); $this->Ln(12); }
    public function Footer(): void { $this->SetY(-15); $this->SetFont('Arial', '', 7); $this->SetTextColor(107, 110, 130); $this->Cell(0, 4, orderPdfText(app_branding()->footerBlock()), 0, 1, 'C'); $this->Cell(0, 4, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C'); }
}
$rows = (new PurchaseOrderRepository(app_database()))->search($_GET);
$labels = ['recu' => 'Recu', 'traitement' => 'En traitement', 'execute' => 'Execute', 'annule' => 'Annule'];
$pdf = new PurchaseOrdersPdf('L', 'mm', 'A4'); $pdf->AliasNbPages(); $pdf->SetMargins(12, 10, 12); $pdf->AddPage(); $pdf->SetFillColor(34, 37, 75); $pdf->SetTextColor(255); $pdf->SetFont('Arial', 'B', 8);
$headers = [['N° interne', 38], ['Bon client', 39], ['Client', 51], ['Devis', 35], ['Date bon', 25], ['Reception', 25], ['Montant', 35], ['Statut', 25]];
foreach ($headers as [$label, $width]) $pdf->Cell($width, 9, orderPdfText($label), 0, 0, 'L', true);
$pdf->Ln(); $pdf->SetFont('Arial', '', 7.5); $pdf->SetTextColor(34, 36, 58); $total = 0.0;
foreach ($rows as $index => $row) { $fill = $index % 2 === 0; if ($fill) $pdf->SetFillColor(246, 246, 249); $values = [[$row['numero_bc'], 38], [$row['reference_client'], 39], [$row['nom_client'] ?: '-', 51], [$row['numero_devis'] ?: '-', 35], [date('d/m/Y', strtotime($row['date_commande'])), 25], [date('d/m/Y', strtotime($row['date_reception'])), 25], [number_format((float) $row['montant'], 0, ',', ' '), 35], [$labels[$row['statut']] ?? $row['statut'], 25]]; foreach ($values as [$value, $width]) $pdf->Cell($width, 9, orderPdfText(mb_strimwidth((string) $value, 0, 34, '...')), 0, 0, 'L', $fill); $pdf->Ln(); $total += (float) ($row['montant'] ?? 0); }
$pdf->Ln(4); $pdf->SetFont('Arial', 'B', 11); $pdf->SetTextColor(34, 37, 75); $pdf->Cell(0, 8, orderPdfText(count($rows) . ' bon(s) - Montant total : ' . number_format($total, 0, ',', ' ') . ' FCFA'), 0, 1, 'R');
$pdf->Output('I', 'bons-commande-fidest-' . date('Y-m-d') . '.pdf');
