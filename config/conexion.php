<?php
declare(strict_types=1);

function conectar(): PDO
{
    $env = require __DIR__ . '/env.php';
    return new PDO(
        "mysql:host={$env['host']};dbname={$env['base']};charset=utf8mb4",
        $env['usuario'],
        $env['clave'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
}
