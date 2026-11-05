<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';

$pdo = conectar();
$marca = 'QA' . bin2hex(random_bytes(6));
$creados = [];
$resultados = [];
$antes = [];
foreach (['producto', 'venta', 'detalle_venta'] as $tabla) {
    $antes[$tabla] = $pdo->query("SELECT * FROM $tabla ORDER BY id")->fetchAll();
}
function exigir(bool $ok, string $mensaje): void {
    if (!$ok) throw new RuntimeException($mensaje);
}
function producto(string $precio, int $stock): int {
    global $pdo, $marca, $creados;
    $s = $pdo->prepare('INSERT INTO producto (nombre, precio_unitario, stock) VALUES (?, ?, ?)');
    $s->execute([$marca . '-' . count($creados), $precio, $stock]);
    $id = (int) $pdo->lastInsertId();
    $creados[] = $id;
    return $id;
}
function enviar(array $items, string $cliente): array {
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 15,
        CURLOPT_URL => 'http://localhost/bodega/venta.php']);
    $form = curl_exec($ch);
    exigir(is_string($form) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200, 'No carga el formulario');
    exigir(preg_match('/name="csrf" value="([a-f0-9]+)"/', $form, $m) === 1, 'Falta token CSRF');
    foreach ($items as $item) exigir(str_contains($form, 'value="' . $item['producto_id'] . '"'), 'Producto ausente del select');
    curl_setopt_array($ch, [CURLOPT_URL => 'http://localhost/bodega/guardar_venta.php', CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['csrf' => $m[1], 'cliente' => $cliente, 'items' => $items])]);
    $html = curl_exec($ch);
    exigir(is_string($html), 'Error HTTP: ' . curl_error($ch));
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($estado === 422) {
        curl_setopt_array($ch, [CURLOPT_URL => 'http://localhost/bodega/venta.php', CURLOPT_POST => false]);
        $correccion = curl_exec($ch);
        exigir(is_string($correccion) && str_contains($correccion, 'value="' . htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8') . '"'), 'No se conserva el cliente al corregir');
        foreach ($items as $item) {
            exigir(str_contains($correccion, '"cantidad":"' . $item['cantidad'] . '"'), 'No se conserva la cantidad al corregir');
        }
    }
    curl_close($ch);
    return [$estado, $html];
}
$casos = [
    ['Stock exacto', '2.50', 5, [5], 200, '12.50', ['12.50'], ['0.00'], 'Venta exitosa; stock 5 → 0.'],
    ['Una unidad más', '2.50', 5, [6], 422, null, [], [], 'Stock insuficiente: hay 5 unidades disponibles'],
    ['Subtotal 100,00', '2.50', 50, [40], 200, '100.00', ['100.00'], ['0.00'], 'Subtotal 100,00; descuento 0,00; total 100,00.'],
    ['Subtotal 100,01', '100.01', 1, [1], 200, '95.01', ['100.01'], ['5.00'], 'Subtotal 100,01; descuento 5,00; total 95,01.'],
    ['Cantidad cero', '2.50', 5, [0], 422, null, [], [], 'La cantidad debe ser un número entero entre 1 y 9.999'],
    ['Cantidad negativa', '2.50', 5, [-1], 422, null, [], [], 'La cantidad debe ser un número entero entre 1 y 9.999'],
    ['Stock acumulado', '2.50', 5, [3, 3], 422, null, [], [], 'Stock insuficiente: hay 2 unidades disponibles'],
    ['Venta de dos productos', '2.50', 50, [44], 200, '112.48', ['110.00', '8.40'], ['5.50', '0.42'], 'Subtotal 118,40; descuento global 5,92; total 112,48; stocks 50 → 6 y 25 → 23.'],
];
try {
    foreach ($casos as $indice => $caso) {
        [$nombre, $precio, $stockInicial, $cantidades, $esperado, $total, $subtotales, $descuentos, $observacion] = $caso;
        try {
            $id = producto($precio, $stockInicial);
            $items = array_map(fn($q) => ['producto_id' => $id, 'cantidad' => (string) $q], $cantidades);
            $stocks = [$id => $stockInicial];
            if ($indice === 7) {
                $id2 = producto('4.20', 25);
                $items[] = ['producto_id' => $id2, 'cantidad' => '2'];
                $stocks[$id2] = 25;
            }
            $cliente = $marca . '-caso-' . ($indice + 1);
            [$estado, $html] = enviar($items, $cliente);
            exigir($estado === $esperado, "HTTP $estado; esperado $esperado");
            $s = $pdo->prepare('SELECT * FROM venta WHERE cliente = ?');
            $s->execute([$cliente]);
            $ventas = $s->fetchAll();
            if ($esperado === 200) {
                exigir(str_contains($html, 'registrada correctamente'), 'Falta mensaje de éxito');
                exigir(count($ventas) === 1 && $ventas[0]['total'] === $total, 'Total persistido incorrecto');
                $s = $pdo->prepare('SELECT * FROM detalle_venta WHERE venta_id = ? ORDER BY id');
                $s->execute([$ventas[0]['id']]);
                $detalles = $s->fetchAll();
                exigir(count($detalles) === count($items), 'Cantidad de detalles incorrecta');
                foreach ($detalles as $j => $detalle) {
                    exigir($detalle['subtotal'] === $subtotales[$j] && $detalle['descuento'] === $descuentos[$j], 'Subtotal o descuento incorrecto');
                    exigir((int) $detalle['producto_id'] === $items[$j]['producto_id'] && (int) $detalle['cantidad'] === (int) $items[$j]['cantidad'], 'Producto o cantidad incorrectos');
                    $neto = number_format((float) $detalle['subtotal'] - (float) $detalle['descuento'], 2, ',', ' ');
                    exigir(str_contains($html, 'S/ ' . $neto), 'Importe neto ausente del resumen HTML');
                }
                foreach ($items as $item) $stocks[$item['producto_id']] -= (int) $item['cantidad'];
            } else {
                exigir(str_contains($html, $observacion), 'Mensaje de error incorrecto');
                exigir(count($ventas) === 0, 'Se guardó una venta rechazada');
            }
            foreach ($stocks as $pid => $esperadoStock) {
                $s = $pdo->prepare('SELECT stock FROM producto WHERE id = ?');
                $s->execute([$pid]);
                exigir((int) $s->fetchColumn() === $esperadoStock, 'Stock final incorrecto');
            }
            $resultados[] = [$indice + 1, $nombre, 'APROBADA', $observacion];
        } catch (Throwable $e) {
            $resultados[] = [$indice + 1, $nombre, 'FALLÓ', $e->getMessage()];
        }
    }
} finally {
    // Limpieza restringida a los registros creados por esta ejecución.
    $pdo->beginTransaction();
    $s = $pdo->prepare('DELETE d FROM detalle_venta d JOIN venta v ON v.id = d.venta_id WHERE v.cliente LIKE ?');
    $s->execute([$marca . '-caso-%']);
    $s = $pdo->prepare('DELETE FROM venta WHERE cliente LIKE ?');
    $s->execute([$marca . '-caso-%']);
    $s = $pdo->prepare('DELETE FROM producto WHERE id = ? AND nombre LIKE ?');
    foreach ($creados as $id) $s->execute([$id, $marca . '-%']);
    $pdo->commit();
}
foreach (['producto', 'venta', 'detalle_venta'] as $tabla) {
    exigir($antes[$tabla] === $pdo->query("SELECT * FROM $tabla ORDER BY id")->fetchAll(), 'Los registros originales cambiaron en ' . $tabla);
}
$reporte = "# Verificación de ocho casos\n\n";
$reporte .= 'Fecha: ' . (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))->format('Y-m-d H:i:s') . " (Lima).\n\n";
$reporte .= "Método: GET del formulario y POST HTTP real con sesión y CSRF; comprobación del HTML de respuesta y de venta, detalles y stock en la base local. No se verificó la interacción visual ni JavaScript porque el acceso al navegador no estuvo disponible.\n\n";
$reporte .= "| N.° | Caso | Resultado | Evidencia |\n|---|---|---|---|\n";
foreach ($resultados as $fila) $reporte .= '| ' . implode(' | ', $fila) . " |\n";
$reporte .= "\nLos productos y ventas de prueba se eliminaron; los registros originales de las tres tablas coinciden con la captura previa. Los contadores AUTO_INCREMENT pueden haber avanzado. El precio 100,01 se configuró exclusivamente en un producto temporal.\n";
file_put_contents(__DIR__ . '/resultados_8.md', $reporte);
echo $reporte;
exit(count(array_filter($resultados, fn($r) => $r[2] !== 'APROBADA')) ? 1 : 0);
