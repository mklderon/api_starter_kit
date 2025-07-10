<?php

namespace Core;

/**
 * Class Framework
 * 
 * The core application class that initializes and runs the application.
 */
class Framework
{
    /** @var Framework|null Singleton instance of the Framework */
    private static $instance;

    /** @var Router The router instance */
    private $router;

    /** @var Database|null The database instance */
    private $db;

    /** @var array Application configuration */
    private $config;

    /** @var array List of global middleware classes */
    private $middleware = [];

    /** @var Logger Logger instance for logging events */
    private $logger;

    /**
     * Framework constructor.
     * Initializes configuration, router, database, and routes.
     * 
     * @throws \Exception If configuration or database initialization fails
     */
    public function __construct()
    {
        self::$instance = $this;
        $this->logger = new Logger();
        $this->loadConfig();
        $this->setTimezone();
        $this->router = new Router();
        $this->initDatabase();
        $this->loadRoutes();
    }

    /**
     * Get the singleton instance of the Framework.
     *
     * @return Framework
     */
    public static function app(): Framework
    {
        return self::$instance;
    }

    /**
     * Load application configuration from config.php and environment variables.
     *
     * @throws \Exception If configuration file is missing or invalid
     */
    private function loadConfig(): void
    {
        $this->loadEnv();
        $configPath = __DIR__ . '/../app/config/config.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            throw new \Exception("Configuration file not found: $configPath");
        }

        if (!is_array($this->config)) {
            throw new \Exception("Configuration is not an array");
        }
    }

    /**
     * Load environment variables from .env file.
     */
    private function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $env = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($env as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if ($key === 'DB_ENABLE' || $key === 'DEBUG') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                    }
                    $_ENV[$key] = $value;
                }
            }
        } else {
            $this->logger->warning("Environment file not found: $envFile");
        }
    }

    /**
     * Set the application timezone.
     */
    private function setTimezone(): void
    {
        $timezone = $this->config['app']['timezone'] ?? 'UTC';

        if (!in_array($timezone, timezone_identifiers_list())) {
            $this->logger->warning("Invalid timezone '{$timezone}', using UTC as fallback");
            $timezone = 'UTC';
        }

        date_default_timezone_set($timezone);

        if (env('DEBUG', false)) {
            $this->logger->info("Timezone set to: " . date_default_timezone_get());
        }
    }

    /**
     * Initialize the database connection if enabled.
     *
     * @throws \Exception If database configuration is missing or connection fails
     */
    private function initDatabase(): void
    {
        $dbEnable = filter_var(env('DB_ENABLE', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        if ($dbEnable) {
            if (!isset($this->config['database']) || !is_array($this->config['database'])) {
                throw new \Exception("Database configuration is missing or invalid");
            }

            // Validate required database configuration keys, allowing empty 'pass'
            $requiredKeys = ['host', 'name', 'user', 'charset'];
            foreach ($requiredKeys as $key) {
                if (!isset($this->config['database'][$key]) || trim($this->config['database'][$key]) === '') {
                    throw new \Exception("Database configuration is missing or empty for key: $key");
                }
            }

            // Ensure 'pass' is set, even if empty
            $this->config['database']['pass'] = $this->config['database']['pass'] ?? '';

            // Attempt to initialize database; let any exception propagate
            $this->db = new Database($this->config['database']);
            $this->logger->info("Database initialized successfully");
        } else {
            $this->db = null;
            $this->logger->info("Database initialization skipped (DB_ENABLE=false)");
        }
    }

    /**
     * Load application routes from routes.php.
     *
     * @throws \Exception If routes file is missing
     */
    private function loadRoutes(): void
    {
        $routesFile = __DIR__ . '/../app/config/routes.php';
        if (file_exists($routesFile)) {
            require $routesFile;
        } else {
            throw new \Exception("Routes file not found: $routesFile");
        }
    }

    /**
     * Add a global middleware to the application.
     *
     * @param string $middleware Middleware class name
     * @return $this
     */
    public function addMiddleware(string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Register a GET route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     * @return $this
     */
    public function get(string $path, $action, array $middlewares = []): self
    {
        $this->router->get($path, $action, $middlewares);
        return $this;
    }

    /**
     * Register a POST route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     * @return $this
     */
    public function post(string $path, $action, array $middlewares = []): self
    {
        $this->router->post($path, $action, $middlewares);
        return $this;
    }

    /**
     * Register a PUT route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     * @return $this
     */
    public function put(string $path, $action, array $middlewares = []): self
    {
        $this->router->put($path, $action, $middlewares);
        return $this;
    }

    /**
     * Register a DELETE route.
     *
     * @param string $path Route path
     * @param callable|array $action Route action
     * @param array $middlewares Route-specific middlewares
     * @return $this
     */
    public function delete(string $path, $action, array $middlewares = []): self
    {
        $this->router->delete($path, $action, $middlewares);
        return $this;
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
        
        $this->router->get("/{$name}", [$controller, 'index'], $middlewares);
        $this->router->get("/{$name}/{id}", [$controller, 'show'], $middlewares);
        $this->router->post("/{$name}", [$controller, 'store'], $middlewares);
        $this->router->put("/{$name}/{id}", [$controller, 'update'], $middlewares);
        $this->router->delete("/{$name}/{id}", [$controller, 'destroy'], $middlewares);
        
        return $this;
    }

    /**
     * Run the application, handling requests and dispatching routes.
     */
    public function run(): void
    {
        try {
            Response::setCorsHeaders($this->config['cors']);
            
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                Response::success(null, 'OK', 200);
                return;
            }
            
            if (!empty($this->middleware)) {
                foreach ($this->middleware as $middleware) {
                    $middlewareClass = "App\\Middleware\\{$middleware}";
                    if (class_exists($middlewareClass)) {
                        $middlewareInstance = new $middlewareClass();
                        if (!$middlewareInstance->handle()) {
                            return;
                        }
                    } else {
                        $this->logger->error("Global middleware class not found: {$middlewareClass}");
                    }
                }
            }
            
            $this->router->dispatch();
            
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle application errors and log them.
     *
     * @param \Exception $e The exception to handle
     */
    private function handleError(\Exception $e): void
    {
        $this->logger->error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if (env('DEBUG', false)) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        } else {
            Response::error('Internal server error', 500);
        }
    }

    /**
     * Get the database instance.
     *
     * @return Database|null
     */
    public function getDatabase(): ?Database
    {
        if (!$this->config['app']['db_enable'] || $this->db === null) {
            $this->logger->warning("Database access attempted but DB_ENABLE is false or database not initialized");
            return null;
        }
        return $this->db;
    }

    /**
     * Get configuration value(s).
     *
     * @param string|null $key Configuration key (optional)
     * @return mixed
     */
    public function getConfig(?string $key = null)
    {
        if ($key) {
            return $this->config[$key] ?? null;
        }
        return $this->config;
    }

    /**
     * Get the configured timezone.
     *
     * @return string
     */
    public function getTimezone(): string
    {
        return date_default_timezone_get();
    }
}