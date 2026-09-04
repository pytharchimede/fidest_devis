<?php
declare(strict_types=1);
namespace App\Application\NeedRequest;
use App\Domain\NeedRequest\NeedRequestRepository;use App\Infrastructure\Branding\Branding;use RuntimeException;
require_once APP_ROOT.'/fpdf186/fpdf.php';
require_once APP_ROOT.'/phpqrcode/qrlib.php';
final class ExportNeedRequestPdf
{
    public function __construct(private readonly NeedRequestRepository $repository,private readonly Branding $branding){}
    public function execute(int $id,string $verificationUrl):array
    {
        $document=$this->repository->document($id);if(!$document)throw new RuntimeException('FEB introuvable.',404);
        $qr=tempnam(sys_get_temp_dir(),'fidest_feb_qr_');if($qr===false)throw new RuntimeException('QR code indisponible.');$qrPng=$qr.'.png';@unlink($qr);\QRcode::png($verificationUrl,$qrPng,QR_ECLEVEL_M,4,2);
        $pdf=new NeedRequestPdf($this->branding,$document,$qrPng);$pdf->AliasNbPages();$pdf->AddPage();$pdf->renderDocument();$content=$pdf->Output('S');@unlink($qrPng);
        return ['content'=>$content,'filename'=>preg_replace('/[^A-Za-z0-9_-]+/','-',(string)$document['document_uid']).'.pdf'];
    }
}

final class NeedRequestPdf extends \FPDF
{
    public function __construct(private readonly Branding $branding,private readonly array $document,private readonly string $qr){parent::__construct('P','mm','A4');$this->SetMargins(14,42,14);$this->SetAutoPageBreak(true,25);}
    private function t(mixed $value):string{$text=(string)$value;$converted=@iconv('UTF-8','windows-1252//TRANSLIT//IGNORE',$text);return $converted===false?$text:$converted;}
    public function Header()
    {
        $logo=APP_ROOT.'/'.$this->branding->get('logo','img/logo_fidest.png');if(is_file($logo))$this->Image($logo,14,9,31);
        $this->SetXY(49,8);$this->SetFont('Arial','B',12);$this->SetTextColor(34,37,75);$this->Cell(92,8,$this->t('FICHE D’EXPRESSION DE BESOIN'),1,0,'C');
        $this->SetFont('Arial','',7);$this->SetXY(141,8);$this->Cell(55,5,$this->t('ID : '.$this->document['document_uid']),1,2,'L');$this->Cell(55,5,$this->t('Version : '.$this->document['document_version']),1,2,'L');$this->Cell(55,5,$this->t('Émission : '.date('d/m/Y',strtotime((string)$this->document['created_at']))),1,2,'L');$this->Cell(55,5,$this->t('Page : '.$this->PageNo().'/{nb}'),1,0,'L');
        $this->SetXY(49,28);$this->SetFont('Arial','I',6.5);$this->SetTextColor(100,102,116);$this->Cell(147,5,$this->t('Document maîtrisé · Présentation inspirée des exigences documentaires ISO 9001'),0,0,'C');$this->SetDrawColor(250,189,2);$this->SetLineWidth(.8);$this->Line(14,36,196,36);
    }
    public function Footer(){$this->SetY(-20);$this->SetDrawColor(34,37,75);$this->Line(14,$this->GetY(),196,$this->GetY());$this->SetFont('Arial','',6.5);$this->SetTextColor(90,92,105);$this->MultiCell(0,3.4,$this->t($this->branding->footerBlock()),0,'C');}
    private function ensureSpace(float $height):bool{if($this->GetY()+$height<=$this->PageBreakTrigger)return false;$this->AddPage();return true;}
    private function section(string $title,float $followingHeight=7):void{$this->ensureSpace(11+$followingHeight);$this->Ln(4);$this->SetFillColor(34,37,75);$this->SetTextColor(255);$this->SetFont('Arial','B',8);$this->Cell(0,7,$this->t($title),0,1,'L',true);$this->SetTextColor(30);}
    private function pair(string $label,mixed $value,float $width=91):void{$this->SetFont('Arial','B',7);$this->SetFillColor(245,246,249);$this->Cell(34,7,$this->t($label),1,0,'L',true);$this->SetFont('Arial','',7);$this->Cell($width-34,7,$this->t($value?:'—'),1,0,'L');}
    private function amount(mixed $value):string{return number_format((float)$value,0,',',' ').' FCFA';}
    private function dateValue(mixed $value):string{return $value?date('d/m/Y',strtotime((string)$value)):'—';}
    private function tableHeader(array $columns):void{$this->SetFillColor(250,189,2);$this->SetTextColor(34,37,75);$this->SetFont('Arial','B',7);foreach($columns as [$label,$width])$this->Cell($width,7,$this->t($label),1,0,'C',true);$this->Ln();$this->SetFont('Arial','',7);$this->SetTextColor(30);}
    public function renderDocument():void
    {
        $d=$this->document;$this->SetXY(14,42);$this->SetFont('Arial','B',15);$this->SetTextColor(34,37,75);$this->Cell(0,10,$this->t($d['numero_feb']),0,1,'L');$this->SetTextColor(30);
        $this->section('1. TRAÇABILITÉ DE LA DEMANDE',21);$this->pair('Client',$d['nom_client']);$this->pair('Appel d’offre',$d['num_offre']);$this->Ln();$this->pair('Devis',$d['numero_devis']);$this->pair('Bon de commande',$d['numero_bc']);$this->Ln();$this->pair('Date du bon',$this->dateValue($d['date_commande']));$this->pair('Disponibilité FEB',$this->dateValue($d['date_disponibilite']));$this->Ln();
        $hasOrderAmount=$d['montant_bc']!==null&&$d['montant_bc']!=='';$orderAmount=(float)($d['montant_bc']??0);$needAmount=(float)$d['montant_demande'];$margin=$orderAmount-$needAmount;$marginRate=$orderAmount>0?($margin/$orderAmount)*100:0;
        $this->section('2. SYNTHÈSE FINANCIÈRE',21);$this->pair('Devis soumis (HT)',$this->amount($d['total_ht']));$this->pair('Devis soumis (TTC)',$this->amount($d['total_ttc']));$this->Ln();$this->pair('Bon de commande',$hasOrderAmount?$this->amount($orderAmount):'—');$this->pair('Coût du besoin',$this->amount($needAmount));$this->Ln();$this->pair('Marge réalisée',$hasOrderAmount?$this->amount($margin):'—');$this->pair('Taux de marge',$hasOrderAmount&&$orderAmount>0?number_format($marginRate,2,',',' ').' %':'—');$this->Ln();
        $quoteColumns=[['Désignation',91],['Qté',19],['P.U.',34],['Total',38]];$this->section('3. DEMANDE DU CLIENT (DEVIS SOUMIS)',14);$this->tableHeader($quoteColumns);foreach(($d['quote_lines']??[]) as $line){if($this->ensureSpace(7))$this->tableHeader($quoteColumns);$this->Cell(91,7,$this->t(mb_strimwidth((string)$line['designation'],0,62,'…')),1);$this->Cell(19,7,number_format((float)$line['quantite'],2,',',' '),1,0,'R');$this->Cell(34,7,number_format((float)$line['prix'],0,',',' '),1,0,'R');$this->Cell(38,7,number_format((float)$line['total'],0,',',' '),1,1,'R');}if(empty($d['quote_lines'])){$this->Cell(182,7,$this->t('Aucune ligne de devis renseignée.'),1,1,'C');}
        $this->section('4. PLANNING D’EXÉCUTION');$this->pair('Phase',$d['planning_label']);$period=$d['date_debut']?$this->dateValue($d['date_debut']).' au '.$this->dateValue($d['date_fin']):'—';$this->pair('Période',$period);$this->Ln();
        $needColumns=[['Catégorie',27],['Désignation',78],['Qté',16],['P.U.',28],['Montant',33]];$this->section('5. EXPRESSION DÉTAILLÉE DU BESOIN INTERNE',14);$this->tableHeader($needColumns);foreach($d['lines'] as $line){if($this->ensureSpace(7))$this->tableHeader($needColumns);$this->Cell(27,7,$this->t(ucfirst((string)$line['categorie'])),1);$this->Cell(78,7,$this->t(mb_strimwidth((string)$line['designation'],0,52,'…')),1);$this->Cell(16,7,number_format((float)$line['quantite'],2,',',' '),1,0,'R');$this->Cell(28,7,number_format((float)$line['prix_unitaire'],0,',',' '),1,0,'R');$this->Cell(33,7,number_format((float)$line['montant'],0,',',' '),1,1,'R');}
        if($this->ensureSpace(8))$this->tableHeader($needColumns);$this->SetFont('Arial','B',8);$this->Cell(149,8,$this->t('TOTAL DU BESOIN'),1,0,'R');$this->Cell(33,8,number_format((float)$d['montant_demande'],0,',',' ').' FCFA',1,1,'R');
        $this->section('6. VISA ET MAÎTRISE DOCUMENTAIRE',29);$y=$this->GetY();$visas=[['Établi par','prepared_name','prepared_at','prepared_signature'],['Analysé par','analyzed_name','analyzed_at','analyzed_signature'],['Approuvé par','approved_name','approved_at','approved_signature']];foreach($visas as $i=>[$title,$nameKey,$dateKey,$signatureKey]){$x=14+$i*52;$this->SetXY($x,$y);$this->SetFont('Arial','B',7);$this->Cell(50,7,$this->t($title),1,2,'C');$this->SetFont('Arial','',6);$name=(string)($d[$nameKey]??'');$date=(string)($d[$dateKey]??'');$this->Cell(50,20,$this->t($name!==''?$name.($date!==''?' · '.date('d/m/Y H:i',strtotime($date)):''):'En attente de visa'),1,0,'C');$signature=(string)($d[$signatureKey]??'');$signatureFile=APP_ROOT.'/'.$signature;if($signature!==''&&is_file($signatureFile)){try{$this->Image($signatureFile,$x+15,$y+10,20,10);}catch(\Throwable){}}}$this->Image($this->qr,174,$y+1,20);$this->SetXY(168,$y+22);$this->SetFont('Arial','',5.5);$this->MultiCell(28,3,$this->t('Scanner pour vérifier le document'),0,'C');
    }
}
