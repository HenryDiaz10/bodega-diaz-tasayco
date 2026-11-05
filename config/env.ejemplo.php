<?php
declare(strict_types=1);
// Copiar a env.php; mantener los secretos exclusivamente en el servidor.
return [
    'host' => getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost',
    'usuario' => getenv('DB_USER') !== false ? getenv('DB_USER') : 'root',
    'clave' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    'base' => getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'bodega',
];
