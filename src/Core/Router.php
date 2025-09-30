<?php
namespace App\Core;

final class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private ?array $lastGroupRange = null; // [start, end] của routes thêm trong group gần nhất

    public function get(string $uri, callable|array $action): self
    {
        return $this->map('GET', $uri, $action);
    }
    public function post(string $uri, callable|array $action): self
    {
        return $this->map('POST', $uri, $action);
    }

    public function group(string $prefix, \Closure $cb): self
    {
        $this->groupStack[] = rtrim($prefix, '/');
        $start = count($this->routes);
        $cb($this);
        array_pop($this->groupStack);
        $end = count($this->routes) - 1;
        $this->lastGroupRange = $end >= $start ? [$start, $end] : null;
        return $this;
    }

    // Gắn middleware: nếu vừa gọi group() thì áp cho toàn bộ routes trong group đó
    public function middleware(string $name): self
    {
        if ($this->lastGroupRange) {
            [$s, $e] = $this->lastGroupRange;
            for ($i = $s; $i <= $e; $i++)
                $this->routes[$i]['mw'][] = $name;
            $this->lastGroupRange = null;
        } else {
            $last = array_key_last($this->routes);
            if ($last !== null)
                $this->routes[$last]['mw'][] = $name;
        }
        return $this;
    }

    private function map(string $method, string $uri, callable|array $action): self
    {
        $prefix = implode('', $this->groupStack);
        $uri = (str_starts_with($uri, '/') ? $uri : '/' . $uri);
        $this->routes[] = [
            'method' => $method,
            'uri' => $prefix . $uri,
            'action' => $action,
            'mw' => [],
        ];
        return $this;
    }

    public function dispatch(Request $req): void
    {
        $path = rtrim($req->path(), '/') ?: '/';
        $method = $req->method();

        foreach ($this->routes as $r) {
            $routePath = rtrim($r['uri'], '/') ?: '/';
            $pattern = "#^" . preg_replace('#\{([\w_]+)\}#', '(?P<$1>[^/]+)', $routePath) . "$#";

            // chấp nhận HEAD như GET
            $methodMatch = ($method === $r['method']) || ($method === 'HEAD' && $r['method'] === 'GET');

            if ($methodMatch && preg_match($pattern, $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

                // TODO: chạy middleware nếu có trong $r['mw']

                $h = $r['action'];
                $out = null;

                if (is_array($h)) {
                    [$cls, $fn] = $h;
                    $obj = new $cls;

                    // Tự inject Request + map tham số route theo tên
                    $rm = new \ReflectionMethod($obj, $fn);
                    $args = [];
                    foreach ($rm->getParameters() as $p) {
                        $type = $p->getType();
                        $isReq =
                            $type instanceof \ReflectionNamedType
                            && $type->getName() === Request::class;

                        if ($isReq) {
                            $args[] = $req;
                        } elseif (array_key_exists($p->getName(), $params)) {
                            $args[] = $params[$p->getName()];
                        } elseif ($p->isDefaultValueAvailable()) {
                            $args[] = $p->getDefaultValue();
                        }
                    }

                    $out = $rm->invokeArgs($obj, $args);
                } else {
                    // callable thuần (closure...)
                    $out = $h(...array_values($params));
                }

                if (is_string($out) || $out instanceof \Stringable) {
                    echo (string) $out;
                }
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

}
