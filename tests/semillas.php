<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
$pdo = conectar();
// La tabla temporal oculta la real solo en esta conexión.
$pdo->exec('CREATE TEMPORARY TABLE producto (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    precio_unitario DECIMAL(10,2) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$sql = file_get_contents(__DIR__ . '/../sql/bodega.sql');
$inicio = strpos($sql, 'INSERT INTO producto');
if ($inicio === false) throw new RuntimeException('No se encontraron las semillas');
$insertar = substr($sql, $inicio);
$pdo->exec($insertar);
$pdo->exec('UPDATE producto SET stock = 17, precio_unitario = 7.25 WHERE id = 1');
$pdo->exec($insertar);
$producto = $pdo->query('SELECT stock, precio_unitario FROM producto WHERE id = 1')->fetch();
if ((int) $pdo->query('SELECT COUNT(*) FROM producto')->fetchColumn() !== 6
    || (int) $producto['stock'] !== 17 || $producto['precio_unitario'] !== '7.25') {
    throw new RuntimeException('La reimportación alteró los productos existentes');
}
echo "Semillas: dos importaciones, seis productos y datos existentes conservados.\n";
