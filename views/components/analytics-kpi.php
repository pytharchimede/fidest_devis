<?php /** @var array $kpi */ ?>
<article class="analytics-kpi" style="--kpi-color:<?=htmlspecialchars($kpi['color'],ENT_QUOTES)?>">
    <div class="analytics-kpi__head"><span class="analytics-kpi__icon"><i class="fa-solid <?=htmlspecialchars($kpi['icon'],ENT_QUOTES)?>"></i></span><span><?=$kpi['title']?></span></div>
    <div class="analytics-kpi__metrics">
        <?php if(isset($kpi['quantity'])):?><div class="analytics-kpi__quantity" title="<?=$kpi['quantity'].' '.$kpi['unit']?>"><span>Quantité</span><strong><?=$kpi['quantity']?></strong></div><?php else:?><div class="analytics-kpi__quantity analytics-kpi__quantity--money"><strong><?=$kpi['primary']?></strong></div><?php endif;?>
        <?php if(isset($kpi['secondary'])):?><div class="analytics-kpi__amount"><span><?=$kpi['secondary_label']?></span><strong title="<?=htmlspecialchars((string)$kpi['secondary'],ENT_QUOTES)?>"><?=$kpi['secondary']?></strong></div><?php endif;?>
    </div>
    <small><?=$kpi['footer']?></small>
</article>
