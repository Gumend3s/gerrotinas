<?php

spl_autoload_register(function (string $class): void {

    if (!preg_match('/^[a-zA-Z0-9_\\\\]+$/', $class)) return;

    $baseDir   = __DIR__;
    $directories = ['Libraries', 'Helpers'];

    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    foreach ($directories as $directory) {
        $file = $baseDir . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $relativePath;
        $realFile = realpath($file);
        $realBase = realpath($baseDir . DIRECTORY_SEPARATOR . $directory);

        if ($realFile === false || $realBase === false) continue;
        if (strncmp($realFile, $realBase, strlen($realBase)) !== 0) continue;
        if (pathinfo($realFile, PATHINFO_EXTENSION) !== 'php') continue;

        require_once $realFile;
        return;
    }

    $phpWordAutoloader = realpath($baseDir . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'PHPOffice' . DIRECTORY_SEPARATOR . 'PHPWord' . DIRECTORY_SEPARATOR . 'Autoloader.php');
    $phpWordBase = realpath($baseDir . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'PHPOffice');

    if ($phpWordAutoloader !== false && $phpWordBase !== false && strncmp($phpWordAutoloader, $phpWordBase, strlen($phpWordBase)) === 0 && !class_exists('PhpOffice\\PhpWord\\Autoloader', false)) {
        require_once $phpWordAutoloader;
        \PhpOffice\PhpWord\Autoloader::register();
    }
});
