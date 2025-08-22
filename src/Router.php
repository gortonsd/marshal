<?php

/**
 * Router - Marshal PHP Framework
 *
 * - Discovers controllers in a user-supplied folder and registers routes
 *   based on #[Route] attributes on each controller class.
 * - Caches routes to a file for fast lookup; cache is rebuilt if controllers change.
 * - User can call loadControllers() to refresh or use the cache as needed.
 *
 * Each controller should:
 *   - Use the #[Route(url: '/path')] attribute on the class
 *   - Extend gortonsd\Marshal\Controller
 *   - Implement methods named get(), post(), etc. for HTTP actions
 *
 * Example controller:
 *
 *   use gortonsd\Marshal\RouteAttribute;
 *   use gortonsd\Marshal\Controller;
 *
 *   #[RouteAttribute(url: '/example')]
 *   class ExampleController extends Controller {
 *       public function get() { ... }
 *       public function post() { ... }
 *   }
 *
 * Usage:
 *   $controllersPath = __DIR__ . '/src/Controllers';
 *   $router = new Router($controllersPath);
 *   $router->run();
 */

namespace gortonsd\Marshal;

class Router {
    private $routes = [];
    private $controllerFolder;
    private $cacheFile = __DIR__ . '/routes.cache.json';

    /**
     * @param string $controllerFolder Path to controllers directory (required)
     * @param bool $refresh If true, always refresh the route cache (default: false)
     */
    public function __construct($controllerFolder, $refresh = false) {
        if (empty($controllerFolder) || !is_dir($controllerFolder)) {
            throw new \InvalidArgumentException('Router requires a valid path to the controllers directory.');
        }
        $this->controllerFolder = $controllerFolder;
        if ($refresh || !file_exists($this->cacheFile)) {
            $this->loadControllers();
        } else {
            $json = file_get_contents($this->cacheFile);
            $this->routes = json_decode($json, true) ?? [];
        }
    }

    /**
     * Loads controllers and manages route cache.
     * If cache is valid, loads from cache. If not, rebuilds and updates cache.
     * Can be called by user to refresh routes if needed.
     * doesn't check for changes in controller files for minimum 1 hour by default.
     */
    public function loadControllers($minCacheAge = 3600) {
        $cacheValid = false;
        if (file_exists($this->cacheFile)) {
            $json = file_get_contents($this->cacheFile);
            $this->routes = json_decode($json, true) ?? [];
            $cacheValid = true;
        }
        if (!$cacheValid || $this->controllersChanged($minCacheAge)) {
            $this->routes = [];
            foreach ($this->getAllPhpFiles($this->controllerFolder) as $file) {
                //require_once $file;
                $contents = file_get_contents($file);
                $namespace = '';
                if (preg_match('/namespace\s+([^;]+);/', $contents, $nsMatches)) {
                    $namespace = trim($nsMatches[1]);
                }

                $className = basename($file, '.php');
                $fqcn = $namespace ? $namespace . '\\' . $className : $className;
                //echo($fqcn."<br>");
                if (class_exists($fqcn)) {
                    $reflection = new \ReflectionClass($fqcn);
                    $attributes = $reflection->getAttributes('gortonsd\\Marshal\\RouteAttributes');
                    if (!empty($attributes)) {
                        /** @var \gortonsd\Marshal\RouteAttributes $routeAttr */
                        $routeAttr = $attributes[0]->newInstance();
                        $url = $routeAttr->url;
                        foreach (["get", "post", "put", "delete", "patch", "options"] as $method) {
                            if ($reflection->hasMethod($method)) {
                                $this->routes[strtoupper($method)][$url] = $fqcn;
                            }
                        }
                    }
                }
                else {
                    //echo("class not found");
                }
            }
            file_put_contents($this->cacheFile, json_encode($this->routes, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Checks if any controller file has changed since the cache was created,
     * but only if the cache file is older than the minimum age (in seconds).
     * 1 hour by default 
     * 
     */
    private function controllersChanged($minCacheAge = 3600) {
        if (!file_exists($this->cacheFile)) return true;
        $cacheTime = filemtime($this->cacheFile);
        // Only check for changes if cache is older than minCacheAge
        if ((time() - $cacheTime) < $minCacheAge) return false;
        foreach ($this->getAllPhpFiles($this->controllerFolder) as $file) {
            if (filemtime($file) > $cacheTime) return true;
        }
        return false;
    }

    /**
     * Recursively get all PHP files in a folder and its subfolders
     */
    private function getAllPhpFiles($dir) {
        $files = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->getAllPhpFiles($path));
            } elseif (is_file($path) && substr($path, -4) === '.php') {
                $files[] = $path;
            }
        }
        return $files;
    }

    /**
     * Runs the router using the current HTTP request method and URI.
     */
    public function run() {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $uri = parse_url($uri, PHP_URL_PATH); // Remove query string
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        if (isset($this->routes[$method][$uri])) {
            $className = $this->routes[$method][$uri];
            $controller = new $className();
            $action = strtolower($method);
            if (method_exists($controller, $action)) {
                return $controller->$action();
            }
        }
        http_response_code(404);
        echo '404 Not Found';
    }
}

