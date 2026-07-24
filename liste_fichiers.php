<?php
header('Content-Type: text/plain; charset=utf-8');

$base = __DIR__;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $base,
        FilesystemIterator::SKIP_DOTS
    )
);

foreach ($iterator as $file) {
    echo str_replace($base . DIRECTORY_SEPARATOR, '', $file->getPathname()) . PHP_EOL;
}