<?php

abstract class Controller
{
    private const BASE_PATH = __DIR__ . '/../../';

    /**
     * Instantiates and returns a Model by name.
     *
     * @param  string      $model     Class name (e.g. 'User').
     * @param  string|null $subfolder Optional subdirectory inside Models/ (e.g. 'Admin').
     * @return object
     *
     * @throws InvalidArgumentException On path traversal in any argument.
     * @throws RuntimeException         If the model file does not exist.
     */
    public function model(string $model, ?string $subfolder = null): object
    {
        $this->guardPathSegment($model, 'model');

        $subPath = '';
        if ($subfolder !== null) {
            $this->guardPathSegment($subfolder, 'subfolder');
            $subPath = rtrim($subfolder, '/') . '/';
        }

        $filePath = self::BASE_PATH . 'app/Models/' . $subPath . $model . '.php';

        if (!file_exists($filePath)) throw new RuntimeException("Model file not found: {$filePath}");

        require_once $filePath;
        return new $model();
    }

    /**
     * Renders a View, injecting $data as local variables into its scope.
     *
     * @param  string $view Path to the view (e.g. 'user/profile').
     * @param  array  $data Key-value pairs exposed as variables inside the view.
     * @return void
     *
     * @throws InvalidArgumentException On path traversal in $view.
     * @throws RuntimeException         If the view file does not exist.
     */
    public function view(string $view, array $data = []): void
    {
        $this->guardPathSegment($view, 'view', allowSlashes: true);
        $filePath = self::BASE_PATH . 'app/Views/' . $view . '.php';

        if (!file_exists($filePath)) throw new RuntimeException("View file not found: {$filePath}");

        extract($data, EXTR_SKIP);
        require_once $filePath;
    }

    /**
     * Guards against path traversal in a path segment.
     *
     * @param  string $segment
     * @param  string $label
     * @param  bool   $allowSlashes
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function guardPathSegment(string $segment, string $label, bool $allowSlashes = false): void
    {
        if (str_contains($segment, '..') || str_contains($segment, "\0")) throw new InvalidArgumentException("Invalid {$label}: path traversal detected.");
        if (!$allowSlashes && (str_contains($segment, '/') || str_contains($segment, '\\'))) throw new InvalidArgumentException("Invalid {$label}: path separators not allowed.");
    }
}
