<?php
declare(strict_types=1);

namespace App\Application\Treasury;

use App\Domain\Treasury\TreasuryRepository;
use App\Infrastructure\Branding\Branding;
use RuntimeException;

require_once APP_ROOT.'/fpdf186/fpdf.php';

final class ExportDisbursementPoint
{
    public function __construct(private readonly TreasuryRepository $repository,private readonly Branding $branding) {}
    public function pdf(int $id): array
    {
        $point=$this->repository->point($id);if(!$point)throw new RuntimeException('Point de décaissement introuvable.',404);$pdf=new DisbursementPointPdf($this->branding,$point);$pdf->AliasNbPages();$pdf->AddPage();$pdf->renderPoint();return ['content'=>$pdf->Output('S'),'filename'=>$point['point_number'].'.pdf'];
    }
    public function csv(int $id): array
    {
        $p=$this->repository->point($id);if(!$p)throw new RuntimeException('Point de décaissement introuvable.',404);$h=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$rows='';foreach($p['lines'] as $l){$source=$l['source_type']==='real'?'Solde réel':'Facture prévisionnelle';$rows.='<tr><td>'.$h($l['numero_feb']).'</td><td>'.$h($l['nom_client']).'</td><td>'.$h($l['numero_bc']).'</td><td>'.$h($source).'</td><td>'.$h($l['numero_facture']).'</td><td>'.$h($l['scheduled_date']).'</td><td class="n">'.$h($l['amount']).'</td><td>'.$h($l['payment_reference']).'</td></tr>';}$content='<!doctype html><html><head><meta charset="UTF-8"><style>body{font-family:Arial;color:#22254b}h1{font-size:20px}.meta{color:#666}table{border-collapse:collapse;width:100%}th{background:#22254b;color:white}th,td{border:1px solid #aaa;padding:7px}.n{text-align:right}.total{background:#fabd02;font-weight:bold}</style></head><body><h1>FIDEST · POINT JOURNALIER DES DÉCAISSEMENTS</h1><p class="meta">'.$h($p['point_number']).' · '.$h($p['point_date']).' · ID '.$h($p['document_uid']).' · ISO 9001 / document maîtrisé</p><table><thead><tr><th>FEB</th><th>Client</th><th>Bon de commande</th><th>Source</th><th>Facture</th><th>Date prévue</th><th>Montant FCFA</th><th>Référence paiement</th></tr></thead><tbody>'.$rows.'<tr class="total"><td colspan="6">TOTAL</td><td class="n">'.$h($p['total_amount']).'</td><td></td></tr></tbody></table><p>Arrêté par : '.$h($p['closed_name']).' · Visa DG : '.$h($p['signed_name']?:'En attente').' · Décaissé par : '.$h($p['executed_name']?:'En attente').'</p><p>'.$h($this->branding->footerBlock()).'</p></body></html>';return ['content'=>$content,'filename'=>$p['point_number'].'.xls'];
    }
}

final class DisbursementPointPdf extends \FPDF
{
    public function __construct(private readonly Branding $branding,private readonly array $point){parent::__construct('L','mm','A4');$this->SetMargins(12,38,12);$this->SetAutoPageBreak(true,22);}
    private function t(mixed $v):string{return (string)(@iconv('UTF-8','windows-1252//TRANSLIT//IGNORE',(string)$v)?:$v);}
    public function Header(){$logo=APP_ROOT.'/'.$this->branding->get('logo');if(is_file($logo))$this->Image($logo,12,8,31);$this->SetXY(48,8);$this->SetFont('Arial','B',13);$this->SetTextColor(34,37,75);$this->Cell(155,9,$this->t('POINT JOURNALIER DES DÉCAISSEMENTS'),1,0,'C');$this->SetFont('Arial','',7);$this->SetXY(203,8);$this->Cell(81,5,$this->t('ID : '.$this->point['document_uid']),1,2);$this->Cell(81,5,$this->t('Version : '.$this->point['document_version']),1,2);$this->Cell(81,5,$this->t('Page : '.$this->PageNo().'/{nb}'),1,2);$this->Cell(81,5,$this->t('ISO 9001 · Document maîtrisé'),1,0);$this->SetDrawColor(250,189,2);$this->SetLineWidth(.8);$this->Line(12,33,285,33);}
    public function Footer(){$this->SetY(-16);$this->SetFont('Arial','',6.5);$this->SetTextColor(95);$this->MultiCell(0,3.2,$this->t($this->branding->footerBlock()),0,'C');}
    private function headerRow():void{$this->SetFillColor(34,37,75);$this->SetTextColor(255);$this->SetFont('Arial','B',7);foreach([['FEB',28],['Client',54],['Bon de commande',35],['Source',42],['Date prévue',25],['Montant',32],['Référence',57]] as [$v,$w])$this->Cell($w,8,$this->t($v),1,0,'C',true);$this->Ln();$this->SetTextColor(25);$this->SetFont('Arial','',7);}
    public function renderPoint():void{$p=$this->point;$this->SetXY(12,39);$this->SetFont('Arial','B',14);$this->SetTextColor(34,37,75);$this->Cell(0,9,$this->t($p['point_number'].' · '.date('d/m/Y',strtotime($p['point_date']))),0,1);$this->SetFont('Arial','',8);$this->Cell(90,7,$this->t('Arrêté par : '.($p['closed_name']?:'—')),1);$this->Cell(90,7,$this->t('Visa DG : '.($p['signed_name']?:'En attente')),1);$this->Cell(93,7,$this->t('Décaissement : '.($p['executed_name']?:'En attente')),1,1);$this->Ln(4);$this->headerRow();foreach($p['lines'] as $l){if($this->GetY()>177){$this->AddPage();$this->headerRow();}$this->Cell(28,8,$this->t($l['numero_feb']),1);$this->Cell(54,8,$this->t(mb_strimwidth((string)$l['nom_client'],0,34,'…')),1);$this->Cell(35,8,$this->t($l['numero_bc']),1);$source=$l['source_type']==='real'?'Solde réel':'Prévision '.$l['numero_facture'];$this->Cell(42,8,$this->t(mb_strimwidth($source,0,27,'…')),1);$this->Cell(25,8,date('d/m/Y',strtotime($l['scheduled_date'])),1,0,'C');$this->Cell(32,8,number_format((float)$l['amount'],0,',',' '),1,0,'R');$this->Cell(57,8,$this->t($l['payment_reference']?:'—'),1,1);}$this->SetFont('Arial','B',9);$this->Cell(216,9,$this->t('TOTAL DU POINT'),1,0,'R');$this->SetFillColor(250,189,2);$this->Cell(57,9,number_format((float)$p['total_amount'],0,',',' ').' FCFA',1,1,'R',true);$this->Ln(7);$y=$this->GetY();$this->Cell(90,25,$this->t('Arrêté le '.date('d/m/Y H:i',strtotime($p['closed_at']))."\n".$p['closed_name']),1,0,'C');$this->Cell(90,25,$this->t($p['dg_signed_at']?'Bon à décaisser · DG\n'.date('d/m/Y H:i',strtotime($p['dg_signed_at'])).' · '.$p['signed_name']:'Visa DG en attente'),1,0,'C');$this->Cell(93,25,$this->t($p['executed_at']?'Décaissé le '.date('d/m/Y H:i',strtotime($p['executed_at'])).' · '.$p['executed_name']:'Décaissement en attente'),1,0,'C');if($p['dg_signature']&&is_file(APP_ROOT.'/'.$p['dg_signature'])){try{$this->Image(APP_ROOT.'/'.$p['dg_signature'],132,$y+9,22,11);}catch(\Throwable){}}}
}
