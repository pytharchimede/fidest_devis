<?php
declare(strict_types=1);

namespace App\Infrastructure\Branding;

final class Branding
{
    private array $values;

    public function __construct(?string $configFile = null)
    {
        $this->values = require $configFile ?? APP_ROOT . '/config/branding.php';
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->values;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function colors(): array
    {
        return $this->values['colors'];
    }

    public function all(): array
    {
        return $this->values;
    }

    public function issuerBlock(): string
    {
        $company = $this->values['company'] ?? [];
        return implode("\n", array_filter([
            $company['legal_name'] ?? $this->values['name'],
            $company['activity'] ?? '',
            $company['address'] ?? '',
            $company['postal_box'] ?? '',
            isset($company['phone']) ? 'Tél. : ' . $company['phone'] : '',
            $company['email'] ?? '',
        ]));
    }

    public function footerBlock(): string
    {
        $company = $this->values['company'] ?? [];
        return implode(' · ', array_filter([
            $company['legal_name'] ?? $this->values['name'],
            $company['address'] ?? '', $company['postal_box'] ?? '', $company['phone'] ?? '',
            $company['email'] ?? '', isset($company['rccm']) ? 'RCCM : ' . $company['rccm'] : '',
            isset($company['tax_account']) ? 'N° CC : ' . $company['tax_account'] : '',
        ]));
    }
}
