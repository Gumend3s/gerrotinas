<?php
define('LOG_FILE', __DIR__ . '/Logs/errors.log');
define('DISPLAY_ERRORS', (defined('APP_ENVIRONMENT') ? APP_ENVIRONMENT : 'production') === 'development');

if (!is_dir(dirname(LOG_FILE)) && !mkdir(dirname(LOG_FILE), 0755, true) && !is_dir(dirname(LOG_FILE))) throw new RuntimeException('Unable to create log directory: ' . dirname(LOG_FILE));

set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $msg = '[' . errorType($error['type']) . "] {$error['message']} in {$error['file']} on line {$error['line']}";
        handleMessage($msg);
    }
});

/**
 * Maps a PHP error code to a human-readable label.
 */
function errorType(int $errno): string
{
    return match ($errno) {
        E_ERROR, E_USER_ERROR   => 'Fatal Error',
        E_WARNING, E_USER_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE, E_USER_NOTICE  => 'Notice',
        default => 'Error',
    };
}

/**
 * Logs the message and renders an alert in development.
 */
function handleMessage(string $message): void
{
    if (DISPLAY_ERRORS) echo '<div class="alert alert-danger m-2">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';

    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, LOG_FILE);
}

/**
 * Handles recoverable errors. Respects the @ operator and error_reporting() mask.
 */
function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    if (!($errno & error_reporting())) return false;

    handleMessage('[' . errorType($errno) . "] $errstr in $errfile on line $errline");

    return true;
}

/**
 * Handles uncaught exceptions. Sends HTTP 500 in production.
 */
function exceptionHandler(Throwable $ex): void
{
    $msg = '[Uncaught Exception] ' . $ex->getMessage() . ' in ' . $ex->getFile() . ' on line ' . $ex->getLine();
    handleMessage($msg);

    if (!DISPLAY_ERRORS && !headers_sent()) {
        http_response_code(500);
        echo '<h1>500 Internal Server Error</h1><p>Something went wrong. Please try again later.</p>';
    }

    exit(1);
}
