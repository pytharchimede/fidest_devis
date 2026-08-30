<?php
declare(strict_types=1);
namespace App\Http;
use RuntimeException;
final class Router
{
    private array $routes=[];
    public function get(string $path,callable $handler):self{$this->routes['GET'][$path]=$handler;return $this;}
    public function post(string $path,callable $handler):self{$this->routes['POST'][$path]=$handler;return $this;}
    public function dispatch(string $method,string $uri):mixed
    {
        $path=parse_url($uri,PHP_URL_PATH)?:'/';$handler=$this->routes[strtoupper($method)][$path]??null;if(!$handler)throw new RuntimeException('Route introuvable.',404);return $handler();
    }
}
