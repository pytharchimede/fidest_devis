<?php
declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(private readonly string $content = '', private readonly int $status = 200, private readonly array $headers = []) {}

    public static function html(string $content, int $status = 200): self { return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']); }
    public static function json(array $data, int $status = 200): self { return new self((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']); }
    public static function binary(string $content,string $contentType,string $filename='',bool $download=false):self{$headers=['Content-Type'=>$contentType,'Content-Length'=>(string)strlen($content)];if($filename!=='')$headers['Content-Disposition']=($download?'attachment':'inline').'; filename="'.str_replace('"','',$filename).'"';return new self($content,200,$headers);}
    public static function redirect(string $location, int $status = 302): self { return new self('', $status, ['Location' => $location]); }

    public function send(): never
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) { header($name . ': ' . $value); }
        echo $this->content;
        exit;
    }
}
