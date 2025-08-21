<?php

namespace gortonsd\Marshal;

abstract class Controller
{
    /**
     * Get the route URL from the Route attribute on the controller class.
     */
    public static function getRouteUrl(): ?string
    {
        $reflection = new \ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(RouteAttribute::class);
        if (!empty($attributes)) {
            /** @var RouteAttribute $routeAttr */
            $routeAttr = $attributes[0]->newInstance();
            return $routeAttr->url;
        }
        return null;
    }
}
