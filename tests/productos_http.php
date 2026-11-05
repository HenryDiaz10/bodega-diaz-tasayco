<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
$pdo = conectar();
$originales = $pdo->query('SELECT * FROM producto ORDER BY id')->fetchAll();
$marca = 'QAProducto' . bin2hex(random_bytes(6));
$base = 'http://localhost/bodega/';
$ch = curl_init();
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 15]);
function comprobar(bool $ok, string $mensaje): void {
    if (!$ok) throw new RuntimeException($mensaje);
}
function peticion(string $ruta, ?array $datos = null): array {
    global $ch, $base;
    curl_setopt_array($ch, [CURLOPT_URL => $base . $ruta, CURLOPT_POST => $datos !== null, CURLOPT_FOLLOWLOCATION => false]);
    if ($datos !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
    $html = curl_exec($ch);
    comprobar(is_string($html), 'Fallo HTTP');
    return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $html];
}
try {
    [$estado, $html] = peticion('producto_nuevo.php');
    comprobar($estado === 200 && preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $m) === 1, 'No abre formulario');
    $token = $m[1];
    [$estado, $html] = peticion('producto_nuevo.php', ['csrf' => $token, 'nombre' => '', 'precio_unitario' => '-1', 'stock' => '1.5']);
    comprobar($estado === 422 && substr_count($html, 'aria-invalid="true"') === 3, 'Faltan errores junto a cada campo');
    [$estado] = peticion('producto_nuevo.php', ['csrf' => 'incorrecto', 'nombre' => $marca, 'precio_unitario' => '2.50', 'stock' => '5']);
    comprobar($estado === 403, 'CSRF no fue rechazado');
    $datos = ['csrf' => $token, 'nombre' => $marca, 'precio_unitario' => '2,50', 'stock' => '5'];
    [$estado] = peticion('producto_nuevo.php', $datos);
    comprobar($estado === 303, 'No se creó el producto');
    [$estado, $html] = peticion('index.php');
    comprobar($estado === 200 && str_contains($html, 'Producto guardado correctamente'), 'Falta mensaje de alta');
    $s = $pdo->prepare('SELECT * FROM producto WHERE nombre = ?');
    $s->execute([$marca]);
    $producto = $s->fetch();
    comprobar($producto && $producto['precio_unitario'] === '2.50' && (int) $producto['stock'] === 5, 'Datos persistidos incorrectos');
    $id = (int) $producto['id'];
    [$estado, $html] = peticion('producto_nuevo.php', $datos);
    comprobar($estado === 422 && str_contains($html, 'El nombre del producto ya existe'), 'No rechaza nombre duplicado');
    $datos['nombre'] = $marca . 'Segundo';
    [$estado] = peticion('producto_nuevo.php', $datos);
    comprobar($estado === 303, 'No se creó segundo producto');
    [$estado, $html] = peticion('producto_editar.php?id=' . $id);
    comprobar($estado === 200 && str_contains($html, $marca), 'No carga la edición');
    [$estado, $html] = peticion('producto_editar.php?id=' . $id, $datos);
    comprobar($estado === 422 && str_contains($html, 'El nombre del producto ya existe'), 'Edición permite nombre de otro producto');
    $datos['nombre'] = $marca;
    $datos['precio_unitario'] = '12.00';
    $datos['stock'] = '0';
    [$estado] = peticion('producto_editar.php?id=' . $id, $datos);
    comprobar($estado === 303, 'No actualiza producto');
    [$estado, $html] = peticion('index.php');
    comprobar($estado === 200 && str_contains($html, 'Producto actualizado correctamente'), 'Falta mensaje de actualización');
    $s->execute([$marca]);
    $producto = $s->fetch();
    comprobar($producto['precio_unitario'] === '12.00' && (int) $producto['stock'] === 0, 'Edición no persistió');
    [$estado] = peticion('producto_editar.php?id=' . $id, $datos);
    comprobar($estado === 303, 'Guardar sin cambios debe funcionar');
    [$estado] = peticion('producto_editar.php?id=invalido');
    comprobar($estado === 404, 'ID inválido debe devolver 404');
    echo "Productos HTTP: alta, edición, duplicados, errores por campo, CSRF y mensajes correctos.\n";
} finally {
    $s = $pdo->prepare('DELETE FROM producto WHERE nombre IN (?, ?)');
    $s->execute([$marca, $marca . 'Segundo']);
    curl_close($ch);
}
comprobar($originales === $pdo->query('SELECT * FROM producto ORDER BY id')->fetchAll(), 'Se modificaron los productos originales');
echo "Productos temporales eliminados; inventario original intacto.\n";
