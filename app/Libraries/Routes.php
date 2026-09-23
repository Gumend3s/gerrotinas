<?php

class Routes
{
    public function __construct()
    {
        $this->autoDispatch($this->getUrl());
    }

    /**
     * Automatically resolves controller, method and parameters from URL segments.
     * Falls back to root if controller, method or parameters are invalid.
     *
     * @param array $segments The URL segments.
     * @return void
     */
    private function autoDispatch(array $segments): void
    {
        $first = ucfirst($this->sanitizeSegment($segments[0] ?? ''));

        if ($first !== '' && file_exists("../app/Controllers/{$first}.php")) {
            $controller = $first;
            $method = $this->sanitizeSegment($segments[1] ?? 'index');
            $params = array_slice($segments, 2);
        } else {
            $controller = 'Page';
            $method = $this->sanitizeSegment($segments[0] ?? 'index');
            $params = array_slice($segments, 1);
        }
        if (!$this->tryDispatch($controller, $method, $params)) Helper::redirect('./');
    }

    /**
     * Attempts to load a controller and call a method on it.
     * Validates parameter count against the method signature via Reflection.
     *
     * @param string $controller The controller class name (already sanitized).
     * @param string $method     The method name (already sanitized).
     * @param array  $params     The parameters to pass to the method.
     * @return bool              True on success, false if anything is invalid.
     */
    private function tryDispatch(string $controller, string $method, array $params): bool
    {
        $path = "../app/Controllers/{$controller}.php";

        if (!file_exists($path)) return false;
        require_once $path;

        $instance = new $controller;
        if (!method_exists($instance, $method)) return false;

        $reflection = new ReflectionMethod($instance, $method);
        $required = $reflection->getNumberOfRequiredParameters();
        $accepted = $reflection->getNumberOfParameters();
        if (count($params) < $required || count($params) > $accepted) return false;

        call_user_func_array([$instance, $method], $params);
        return true;
    }

    /**
     * Retrieves and sanitizes the URL from the query string.
     *
     * @return array The URL segments.
     */
    private function getUrl(): array
    {
        $url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL) ?? '';
        return array_values(array_filter(explode('/', rtrim($url, '/'))));
    }

    /**
     * Strips any characters that are not alphanumeric or underscores.
     * Prevents path traversal and injection via URL segments.
     *
     * @param string $segment A raw URL segment.
     * @return string         The sanitized segment.
     */
    private function sanitizeSegment(string $segment): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $segment);
    }
}
