<?php

namespace Core;

/**
 * Class Router
 * 
 * Handles HTTP request routing and dispatching.
 */
class Router
{
    /** @var array Registered routes by HTTP method */
    private $routes = [];

    /** @var array Route-specific middleware classes */
    private $routeMiddlewares = [];

    /** @var Logger Logger instance for logging routing events */
    private $logger;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * Register a GET route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     */
    public function get(string $path, $action, array $middlewares = []): void
    {
        $this->routes['GET'][$path] = $action;
        $this->routeMiddlewares['GET'][$path] = $middlewares;
    }

    /**
     * Register a POST route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     */
    public function post(string $path, $action, array $middlewares = []): void
    {
        $this->routes['POST'][$path] = $action;
        $this->routeMiddlewares['POST'][$path] = $middlewares;
    }

    /**
     * Register a PUT route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     */
    public function put(string $path, $action, array $middlewares = []): void
    {
        $this->routes['PUT'][$path] = $action;
        $this->routeMiddlewares['PUT'][$path] = $middlewares;
    }

    /**
     * Register a DELETE route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     */
    public function delete(string $path, $action, array $middlewares = []): void
    {
        $this->routes['DELETE'][$path] = $action;
        $this->routeMiddlewares['DELETE'][$path] = $middlewares;
    }

    /**
     * Register a resource route with standard CRUD operations.
     *
     * @param string $name Resource name
     * @param string|null $controller Controller class name
     * @param array $middlewares Route-specific middlewares
     * @return $this
     */
    public function resource(string $name, ?string $controller = null, array $middlewares = []): self
    {
        $controller = $controller ?: ucfirst($name) . 'Controller';
        
        $this->get("/{$name}", [$controller, 'index'], $middlewares);
        $this->get("/{$name}/{id}", [$controller, 'show'], $middlewares);
        $this->post("/{$name}", [$controller, 'store'], $middlewares);
        $this->put("/{$name}/{id}", [$controller, 'update'], $middlewares);
        $this->delete("/{$name}/{id}", [$controller, 'destroy'], $middlewares);
        
        return $this;
    }

    /**
     * Dispatch the request to the appropriate route.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getPath();
    
        if (!isset($this->routes[$method])) {
            $this->notFound();
            return;
        }
    
        foreach ($this->routes[$method] as $route => $action) {
            $params = $this->matchRoute($route, $path);
            if ($params !== false) {
                if (isset($this->routeMiddlewares[$method][$route]) && !empty($this->routeMiddlewares[$method][$route])) {
                    foreach ($this->routeMiddlewares[$method][$route] as $middleware) {
                        $middlewareClass = "App\\Middleware\\{$middleware}";
                        
                        if (class_exists($middlewareClass)) {
                            $middlewareInstance = new $middlewareClass();
                            
                            if (!$middlewareInstance->handle()) {
                                $this->logger->warning("Middleware {$middlewareClass} blocked request", [
                                    'path' => $path,
                                    'method' => $method
                                ]);
                                return;
                            }
                        } else {
                            $this->logger->error("Middleware class not found: {$middlewareClass}", [
                                'path' => $path,
                                'method' => $method
                            ]);
                            Response::error('Internal server error', 500);
                            return;
                        }
                    }
                }
                
                $this->executeAction($action, $params);
                return;
            }
        }
    
        $this->notFound();
    }

    /**
     * Get the request path, accounting for BASE_PATH.
     *
     * @return string
     */
    private function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'];
        $path = parse_url($path, PHP_URL_PATH);

        $basePath = env('BASE_PATH', '');
        
        if (!empty($basePath)) {
            $basePath = '/' . trim($basePath, '/');
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath)) ?: '/';
            }
        }

        return rtrim($path, '/') ?: '/';
    }

    /**
     * Match a route pattern against the request path.
     *
     * @param string $route Route pattern
     * @param string $path Request path
     * @return array|false Route parameters or false if no match
     */
    private function matchRoute(string $route, string $path)
    {
        $routePattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
        $routePattern = '#^' . $routePattern . '$#';

        if (preg_match($routePattern, $path, $matches)) {
            array_shift($matches);
            return $matches;
        }

        return false;
    }

    /**
     * Execute the route action.
     *
     * @param callable|array $action Route action
     * @param array $params Route parameters
     * @throws \Exception If controller or method is not found
     */
    private function executeAction($action, array $params): void
    {
        try {
            if (is_array($action)) {
                [$controller, $method] = $action;
                $controllerClass = "App\\Controllers\\{$controller}";
                $controllerPath = __DIR__ . "/../app/controllers/{$controller}.php";

                if (file_exists($controllerPath)) {
                    require_once $controllerPath;
                    
                    if (class_exists($controllerClass)) {
                        $controllerInstance = new $controllerClass();
                    } else {
                        $controllerInstance = new $controller();
                    }
                    
                    if (method_exists($controllerInstance, $method)) {
                        $this->logger->info("Executing controller action", [
                            'controller' => $controller,
                            'method' => $method,
                            'params' => $params
                        ]);
                        call_user_func_array([$controllerInstance, $method], $params);
                    } else {
                        throw new \Exception("Method {$method} not found in controller {$controller}");
                    }
                } else {
                    throw new \Exception("Controller {$controller} not found");
                }
            } elseif (is_callable($action)) {
                $this->logger->info("Executing callable action", ['params' => $params]);
                call_user_func_array($action, $params);
            } else {
                throw new \Exception("Invalid action type");
            }
        } catch (\Exception $e) {
            $this->logger->error("Action execution failed: " . $e->getMessage(), [
                'action' => is_array($action) ? $action : 'callable',
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Handle route not found.
     */
    private function notFound(): void
    {
        $this->logger->warning("Route not found", [
            'method' => $_SERVER['REQUEST_METHOD'],
            'path' => $this->getPath()
        ]);
        Response::error('Route not found', 404);
    }
}