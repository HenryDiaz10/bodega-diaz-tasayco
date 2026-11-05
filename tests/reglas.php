<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/ServicioVenta.php';

function verificar(bool $condicion, string $caso): void
{
    if (!$condicion) throw new RuntimeException('Falló: ' . $caso);
}
foreach ([0, -1, 10000] as $cantidad) {
    verificar(!ServicioVenta::validarCantidad($cantidad), 'cantidad fuera de rango');
}
foreach ([1, 9999] as $cantidad) {
    verificar(ServicioVenta::validarCantidad($cantidad), 'cantidad válida');
}
verificar(!ServicioVenta::hayStock(1, 0), 'stock cero');
verificar(ServicioVenta::hayStock(5, 5), 'stock exacto');
verificar(!ServicioVenta::hayStock(6, 5), 'stock insuficiente');
verificar(ServicioVenta::calcularDescuento(100.0) === 0.0, 'sin descuento en 100');
verificar(ServicioVenta::calcularDescuento(100.01) === 5.0, 'descuento sobre 100');
verificar(ServicioVenta::calcularDescuento(100.10) === 5.01, 'redondeo mitad hacia arriba');
verificar(ServicioVenta::calcularSubtotal([
    ['precio_unitario' => '2.50', 'cantidad' => 3],
    ['precio_unitario' => '4.20', 'cantidad' => 2],
]) === 15.9, 'subtotal de varias líneas');
// R2 se evalúa una vez sobre el subtotal global de la venta.
verificar(ServicioVenta::calcularTotal(120) === 114.0, 'dos líneas de 60 reciben descuento global');
verificar(ServicioVenta::calcularTotal(120) === 114.0, 'neto con descuento');
foreach (['0', '-1', '1.5', '10000'] as $texto) {
    try {
        ServicioVenta::validarEntrada('Cliente', [['producto_id' => '1', 'cantidad' => $texto]], true);
        throw new LogicException('Debió rechazar la cantidad ' . $texto);
    } catch (InvalidArgumentException $e) {
        verificar($e->getMessage() === 'La cantidad debe ser un número entero entre 1 y 9.999', 'mensaje R3');
    }
}
try {
    ServicioVenta::validarEntrada('Cliente', []);
    throw new LogicException('Debió rechazar una venta vacía');
} catch (InvalidArgumentException $e) {
    verificar($e->getMessage() === 'Debe agregar al menos un producto a la venta', 'venta vacía');
}
$entrada = ServicioVenta::validarEntrada(' Cliente ', [
    ['producto_id' => '1', 'cantidad' => '4'], ['producto_id' => '1', 'cantidad' => '6'],
], true);
verificar($entrada['cliente'] === 'Cliente' && $entrada['items'][0]['cantidad'] === 4, 'normalización');
$productos = [1 => ['nombre' => 'Arroz', 'precio_unitario' => '12.00', 'stock' => 10]];
$preparada = ServicioVenta::prepararVenta($entrada['items'], $productos);
verificar($preparada['total'] === 114.0, 'descuento sobre el subtotal global en preparación');
verificar($productos[1]['stock'] === 10, 'preparación sin modificar los datos de entrada');
try {
    ServicioVenta::prepararVenta($entrada['items'], [1 => ['nombre' => 'Arroz', 'precio_unitario' => '12.00', 'stock' => 9]]);
    throw new LogicException('Debió rechazar stock acumulado');
} catch (InvalidArgumentException $e) {
    verificar($e->getMessage() === 'Stock insuficiente: hay 5 unidades disponibles', 'stock acumulado en servicio');
}
echo "Reglas R1, R2 y R3: verificaciones correctas.\n";
try {
    ServicioVenta::prepararVenta([], []);
    throw new LogicException('Debió rechazar una preparación vacía');
} catch (InvalidArgumentException $e) {
    verificar($e->getMessage() === 'Debe agregar al menos un producto a la venta', 'preparación vacía');
}
try {
    ServicioVenta::prepararVenta([
        ['producto_id' => 1, 'cantidad' => 9999], ['producto_id' => 2, 'cantidad' => 9999],
    ], [
        1 => ['nombre' => 'Producto A', 'precio_unitario' => '99999.99', 'stock' => 9999],
        2 => ['nombre' => 'Producto B', 'precio_unitario' => '99999.99', 'stock' => 9999],
    ]);
    throw new LogicException('Debió rechazar un total fuera de DECIMAL(10,2)');
} catch (InvalidArgumentException $e) {
    verificar(str_contains($e->getMessage(), 'importe máximo'), 'límite monetario');
}
