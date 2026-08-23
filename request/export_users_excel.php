<?php

declare(strict_types=1);
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../bootstrap.php';

$users = app_database()->query('SELECT mail_pro, nom, prenom, modifier_devis, visualiser_devis, soumettre_devis, masquer_devis, envoyer_devis, valider_devis, active, created_at FROM user_devis ORDER BY nom, prenom')->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="equipe-fidest-' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');
function excelText(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
}
$columns = ['Prénom', 'Nom', 'Email professionnel', 'Statut', 'Créé le', 'Modifier', 'Visualiser', 'Soumettre', 'Masquer', 'Envoyer', 'Valider'];
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Styles>
        <Style ss:ID="Title">
            <Font ss:Bold="1" ss:Size="16" ss:Color="#22254B" />
        </Style>
        <Style ss:ID="Header">
            <Font ss:Bold="1" ss:Color="#FFFFFF" /><Interior ss:Color="#22254B" ss:Pattern="Solid" />
        </Style>
    </Styles>
    <Worksheet ss:Name="Équipe">
        <Table>
            <Row>
                <Cell ss:StyleID="Title" ss:MergeAcross="10"><Data ss:Type="String">FIDEST - Équipe et droits d'accès</Data></Cell>
            </Row>
            <Row ss:StyleID="Header"><?php foreach ($columns as $column): ?><Cell><Data ss:Type="String"><?= excelText($column) ?></Data></Cell><?php endforeach; ?></Row>
            <?php foreach ($users as $user): ?><Row><?php foreach ([$user['prenom'], $user['nom'], $user['mail_pro'], $user['active'] ? 'Actif' : 'Désactivé', $user['created_at'], $user['modifier_devis'] ? 'Oui' : 'Non', $user['visualiser_devis'] ? 'Oui' : 'Non', $user['soumettre_devis'] ? 'Oui' : 'Non', $user['masquer_devis'] ? 'Oui' : 'Non', $user['envoyer_devis'] ? 'Oui' : 'Non', $user['valider_devis'] ? 'Oui' : 'Non'] as $value): ?><Cell><Data ss:Type="String"><?= excelText($value) ?></Data></Cell><?php endforeach; ?></Row><?php endforeach; ?>
        </Table>
        <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
            <FreezePanes />
            <FrozenNoSplit />
            <SplitHorizontal>2</SplitHorizontal>
        </WorksheetOptions>
    </Worksheet>
</Workbook>