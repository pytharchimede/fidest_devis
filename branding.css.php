<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$branding = new App\Infrastructure\Branding\Branding();
$brand = $branding->all();

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=3600');
?>
:root {
<?php foreach ($brand['colors'] as $name => $value): ?>
    --brand-<?= str_replace('_', '-', $name) ?>: <?= $value ?>;
<?php endforeach; ?>
    --brand-font-body: "<?= $brand['typography']['body'] ?>", Arial, sans-serif;
    --brand-font-heading: "<?= $brand['typography']['heading'] ?>", Arial, sans-serif;
    --brand-radius-sm: <?= $brand['shape']['radius_small'] ?>;
    --brand-radius-md: <?= $brand['shape']['radius_medium'] ?>;
    --brand-radius-lg: <?= $brand['shape']['radius_large'] ?>;
    --brand-logo: url("<?= $brand['logo'] ?>");
}
