<?php
declare(strict_types=1);
namespace App\Http;
use App\Infrastructure\Container\Container;
final class Router
{
    private array $routes=[];
    public function __construct(private readonly ?Container $container=null,private readonly string $basePath=''){}
    public function get(string $path,callable|array $handler,?string $name=null):self{return $this->add('GET',$path,$handler,$name);}
    public function post(string $path,callable|array $handler,?string $name=null):self{return $this->add('POST',$path,$handler,$name);}
    public function add(string $method,string $path,callable|array $handler,?string $name=null):self
    {
        $normalized='/' . trim($path,'/'); if($normalized==='//')$normalized='/';
        $pattern=preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',static fn($m)=>'(?P<'.$m[1].'>[^/]+)',$normalized);
        $this->routes[strtoupper($method)][]=['path'=>$normalized,'pattern'=>'#^'.$pattern.'$#','handler'=>$handler,'name'=>$name];return $this;
    }
    public function dispatch(Request $request):Response
    {
        $path=$request->path();
        if($this->basePath!==''&&str_starts_with($path,$this->basePath)){$path=substr($path,strlen($this->basePath))?:'/';}
        foreach($this->routes[$request->method()]??[] as $route){
            if(!preg_match($route['pattern'],$path,$matches))continue;
            $params=array_filter($matches,'is_string',ARRAY_FILTER_USE_KEY);
            $handler=$route['handler'];
            if(is_array($handler)&&is_string($handler[0])){$handler=[($this->container??throw new \RuntimeException('Conteneur absent.'))->get($handler[0]),$handler[1]];}
            $result=$handler($request,$params);
            if($result instanceof Response)return $result;
            if(is_array($result))return Response::json($result);
            return Response::html((string)$result);
        }
        return Response::html('<h1>404</h1><p>Route introuvable.</p>',404);
    }
}
