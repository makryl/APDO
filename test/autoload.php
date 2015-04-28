<?php

spl_autoload_register(function ($class) {
    $class = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (stream_resolve_include_path($class)) {
        require $class;
    }
});
