<?php

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../' . preg_replace('#\\\|_(?!.+\\\)#', '/', $class) . '.php';
    if (stream_resolve_include_path($file)) {
        require $file;
    }
});
