<?php

namespace gortonsd\Marshal;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteAttributes {
    public string $url;
    public ?string $name;
    public array $middleware;

    /**
     * @param string $url The route path (e.g. '/example')
     * @param string|null $name Optional route name
     * @param array $middleware Optional middleware list
     */
    public function __construct(
        string $url,
        ?string $name = null,
        array $middleware = []
    ) {
        $this->url = $url;
        $this->name = $name;
        $this->middleware = $middleware;
    }
}
