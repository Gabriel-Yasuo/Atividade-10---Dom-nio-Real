<?php
spl_autoload_register(function (string $class): void {
    $base = __DIR__ . '/../src/';
    $rel  = str_replace('TaskMaster\\', '', $class);
    $file = $base . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
