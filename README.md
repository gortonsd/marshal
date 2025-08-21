
# gortonsd/Marshal

Marshal is a lightweight PHP framework designed to make building web applications simple and intuitive. With Marshal, you can focus on your application's logic while the framework handles routing, controller discovery, and more.

## Key Features

- **Attribute-Based Routing**: Define your routes using PHP attributes. Simply add a `#[RouteAttribute(url: '/your-url')]` attribute to your controller class and Marshal will automatically register the route.
- **Simple Controller Inheritance**: Extend `gortonsd\Marshal\Controller` to create your own controllers. Marshal takes care of all the heavy lifting, so you only need to implement your HTTP methods (`get()`, `post()`, etc.).
- **Automatic Controller Discovery**: Marshal scans your controllers folder and registers routes automatically, keeping your codebase clean and organized.
- **Ready for Expansion**: The framework is designed to be extended, with plans for request/response helpers, middleware, and more.

## Getting Started

1. **Install Marshal** (coming soon via Composer)
2. **Create a Controller**

```php
use gortonsd\Marshal\RouteAttribute;
use gortonsd\Marshal\Controller;

#[RouteAttribute(url: '/example')]

class ExampleController extends Controller {
	public function get() {
		echo "Hello from ExampleController!";
	}
}
```

> **Note:** Controllers can also specify optional `name` and `middleware` parameters in the `RouteAttributes` attribute for advanced routing and access control.


3. **Run Marshal**

```php
use gortonsd\Marshal\Router;

$controllersPath = __DIR__ . '/Controllers'; // Path to your controllers
$router = new Router($controllersPath);
$router->run();
```

## Philosophy

Marshal aims to provide a modern, minimal, and developer-friendly experience for PHP web development. By leveraging PHP attributes and convention over configuration, Marshal lets you build robust applications with less boilerplate.

---

*Marshal: The simple way to marshal your web requests.*
