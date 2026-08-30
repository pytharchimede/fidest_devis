<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $files = [],
        private readonly array $server = [],
    ) {}

    public static function capture(): self
    {
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
        );
    }

    public function method(): string { return $this->method; }
    public function uri(): string { return $this->uri; }
    public function path(): string { return rawurldecode((string) (parse_url($this->uri, PHP_URL_PATH) ?: '/')); }
    public function query(?string $key = null, mixed $default = null): mixed { return $key === null ? $this->query : ($this->query[$key] ?? $default); }
    public function input(?string $key = null, mixed $default = null): mixed { $input = $this->query + $this->body; return $key === null ? $input : ($input[$key] ?? $default); }
    public function files(): array { return $this->files; }
    public function server(string $key, mixed $default = null): mixed { return $this->server[$key] ?? $default; }
}
