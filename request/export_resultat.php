<?php

session_start();
include('../fpdf186/fpdf.php');
include('../../logi/connex.php');
require_once("../phpqrcode/qrlib.php");

class PDF extends FPDF {
    // En-tête du PDF
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Liste des Devis', 0, 1, 'C');
        $this->Ln(10);
    }

    // Pied de page du PDF
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

// Récupération des devis depuis la base de données
$stmt = $con->prepare("SELECT * FROM devis WHERE masque=0");
$stmt->execute();
$devisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Création du PDF en mode paysage
$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

// Ajout des en-têtes de tableau
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(30, 10, utf8_decode('N° Devis'), 1, 0, 'C', true);
$pdf->Cell(60, 10, 'Client', 1, 0, 'C', true);
$pdf->Cell(70, 10, 'Offre', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Date', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Montant Total', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Statut', 1, 1, 'C', true);

// Ajout des données des devis
$pdf->SetFont('Arial', '', 10);
foreach ($devisList as $devis) {
    
    // Récupération des informations du client et de l'offre
    $stmt = $con->prepare("SELECT nom_client FROM client WHERE id_client = ?");
    $stmt->execute([$devis['client_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $con->prepare("SELECT description_offre FROM offre WHERE id_offre = ?");
    $stmt->execute([$devis['offre_id']]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ajout des données dans le tableau
    $pdf->Cell(30, 10, $devis['numero_devis'], 1);
    $pdf->Cell(60, 10, utf8_decode($client['nom_client']), 1);
    $pdf->Cell(70, 10, utf8_decode($offre['description_offre']), 1);
    $pdf->Cell(40, 10, date('d/m/Y', strtotime($devis['date_creation'])), 1);
    $pdf->Cell(40, 10, number_format($devis['montant_total'], 2, ',', ' ') . ' F CFA', 1);
    $pdf->Cell(30, 10, utf8_decode($devis['statut']), 1, 1);
    
}

// Sortie du fichier PDF
$pdf->Output('I', 'liste_devis.pdf');
?>
