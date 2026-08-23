<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';

use App\Domain\Quote\QuoteSearchRepository;

$quotes = (new QuoteSearchRepository(app_database()))->search($_GET);
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="devis-fidest-' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');
function xml_value(mixed $value): string { return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8'); }
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#22254B" ss:Pattern="Solid"/></Style><Style ss:ID="Amount"><NumberFormat ss:Format="#,##0 &quot;FCFA&quot;"/></Style></Styles>
<Worksheet ss:Name="Devis"><Table>
<Row ss:StyleID="Header"><Cell><Data ss:Type="String">N° devis</Data></Cell><Cell><Data ss:Type="String">Client</Data></Cell><Cell><Data ss:Type="String">Destinataire</Data></Cell><Cell><Data ss:Type="String">Émetteur</Data></Cell><Cell><Data ss:Type="String">Date émission</Data></Cell><Cell><Data ss:Type="String">Date expiration</Data></Cell><Cell><Data ss:Type="String">Montant HT</Data></Cell><Cell><Data ss:Type="String">Montant TTC</Data></Cell><Cell><Data ss:Type="String">Statut</Data></Cell></Row>
<?php foreach ($quotes as $quote):
    $validated = !empty($quote['validation_commerciale']) && !empty($quote['validation_generale']);
    $expired = !empty($quote['date_expiration']) && $quote['date_expiration'] < date('Y-m-d');
    $status = $validated ? 'Validé' : ($expired ? 'Expiré' : 'En cours'); ?>
<Row><Cell><Data ss:Type="String"><?= xml_value($quote['numero_devis']) ?></Data></Cell><Cell><Data ss:Type="String"><?= xml_value($quote['nom_client']) ?></Data></Cell><Cell><Data ss:Type="String"><?= xml_value($quote['destine_a']) ?></Data></Cell><Cell><Data ss:Type="String"><?= xml_value($quote['emis_par']) ?></Data></Cell><Cell><Data ss:Type="String"><?= xml_value($quote['date_emission']) ?></Data></Cell><Cell><Data ss:Type="String"><?= xml_value($quote['date_expiration']) ?></Data></Cell><Cell ss:StyleID="Amount"><Data ss:Type="Number"><?= (float) $quote['total_ht'] ?></Data></Cell><Cell ss:StyleID="Amount"><Data ss:Type="Number"><?= (float) $quote['total_ttc'] ?></Data></Cell><Cell><Data ss:Type="String"><?= $status ?></Data></Cell></Row>
<?php endforeach; ?>
</Table></Worksheet></Workbook>
