<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../src/RepositorioMySQL.php';
require_once __DIR__ . '/reglas.php';

// Tablas temporales de sesión: no modifica los datos reales de la bodega.
$pdo = conectar();
$pdo->exec('CREATE TEMPORARY TABLE producto (id INT UNSIGNED PRIMARY KEY, nombre VARCHAR(100), precio_unitario DECIMAL(10,2), stock INT UNSIGNED NOT NULL) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE venta (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, cliente VARCHAR(100), total DECIMAL(10,2)) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE detalle_venta (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, venta_id INT UNSIGNED, producto_id INT UNSIGNED, cantidad INT UNSIGNED, precio_unitario DECIMAL(10,2), subtotal DECIMAL(10,2), descuento DECIMAL(10,2)) ENGINE=InnoDB');
$pdo->exec("INSERT INTO producto VALUES (1, 'Arroz', 12.00, 30), (2, 'Atún', 5.50, 0)");
$repo = new RepositorioMySQL($pdo);
$venta = $repo->registrarVenta('Cliente de prueba', [
    ['producto_id' => 1, 'cantidad' => 4], ['producto_id' => 1, 'cantidad' => 6],
]);
verificar($venta['total'] === 114.0, 'descuento global en dos líneas');
verificar((int) $repo->obtenerProducto(1)['stock'] === 20, 'commit descuenta stock');
try {
    $repo->registrarVenta('Sin stock', [['producto_id' => 2, 'cantidad' => 1]]);
    throw new LogicException('Debió rechazar stock cero');
} catch (InvalidArgumentException $e) {
    verificar($e->getMessage() === 'Stock insuficiente: hay 0 unidades disponibles', 'mensaje stock');
}
try {
    $repo->registrarVenta('Duplicados', [['producto_id' => 1, 'cantidad' => 15], ['producto_id' => 1, 'cantidad' => 6]]);
    throw new LogicException('Debió rechazar stock acumulado');
} catch (InvalidArgumentException $e) {
    verificar((int) $repo->obtenerProducto(1)['stock'] === 20, 'sin cambios por stock acumulado');
}
// Falla real al insertar el segundo detalle, después de actualizar el primero.
$pdo->exec('ALTER TABLE detalle_venta ADD UNIQUE KEY una_linea_por_producto (venta_id, producto_id, cantidad)');
try {
    $repo->registrarVenta('Rollback', [['producto_id' => 1, 'cantidad' => 1], ['producto_id' => 1, 'cantidad' => 1]]);
    throw new LogicException('Debió fallar el segundo detalle');
} catch (RuntimeException $e) {
    verificar($e->getMessage() === 'No se pudo registrar la venta. Intente nuevamente', 'error legible');
}
verificar((int) $repo->obtenerProducto(1)['stock'] === 20, 'rollback restaura stock');
verificar((int) $pdo->query('SELECT COUNT(*) FROM venta')->fetchColumn() === 1, 'rollback restaura ventas');
verificar((int) $pdo->query('SELECT COUNT(*) FROM detalle_venta')->fetchColumn() === 2, 'rollback restaura detalles');
echo "R4: commit, stock acumulado y rollback verificados con tablas temporales.\n";
