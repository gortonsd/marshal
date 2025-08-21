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
    private $cacheFile = __DIR__ . '/routes.cache';

    /**
     * @param string $controllerFolder Path to controllers directory (required)
     */
    public function __construct($controllerFolder) {
        if (empty($controllerFolder) || !is_dir($controllerFolder)) {
            throw new \InvalidArgumentException('Router requires a valid path to the controllers directory.');
        }
        $this->controllerFolder = $controllerFolder;
        $this->loadControllers();
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
            $this->routes = unserialize(file_get_contents($this->cacheFile));
            $cacheValid = true;
        }
        if (!$cacheValid || $this->controllersChanged($minCacheAge)) {
            $this->routes = [];
            foreach (glob($this->controllerFolder . '/*.php') as $file) {
                //require_once $file;
                $contents = file_get_contents($file);
                $namespace = '';
                if (preg_match('/namespace\s+([^;]+);/', $contents, $nsMatches)) {
                    $namespace = trim($nsMatches[1]);
                }

                $className = basename($file, '.php');
                $fqcn = $namespace ? $namespace . '\\' . $className : $className;
                //echo($fqcn."\r\n");
                if (class_exists($fqcn)) {
                    echo("class found");
                    $reflection = new \ReflectionClass($fqcn);
                    $doc = $reflection->getDocComment();
                    //echo($doc."\r\n");
                    if ($doc && preg_match('/@url\s+(\S+)/', $doc, $matches)) {
                        $url = $matches[1];
                        //echo($url);
                        foreach (["get", "post"] as $method) {
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
            file_put_contents($this->cacheFile, serialize($this->routes));
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
        foreach (glob($this->controllerFolder . '/*.php') as $file) {
            if (filemtime($file) > $cacheTime) return true;
        }
        return false;
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

