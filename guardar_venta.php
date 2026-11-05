<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/ServicioVenta.php';
require_once __DIR__ . '/src/RepositorioMySQL.php';
$resultado = null;
$error = '';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        throw new InvalidArgumentException('Envíe la venta desde el formulario.');
    }
    if (!is_string($_POST['csrf'] ?? null) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        throw new InvalidArgumentException('El formulario ha caducado. Abra una nueva venta.');
    }
    // Conservar el pedido para corregir un error sin volver a introducirlo.
    $_SESSION['borrador_venta'] = [
        'cliente' => is_string($_POST['cliente'] ?? null) ? $_POST['cliente'] : '',
        'items' => is_array($_POST['items'] ?? null) ? $_POST['items'] : [],
    ];
    $entrada = ServicioVenta::validarEntrada($_POST['cliente'] ?? '', $_POST['items'] ?? [], true);
    $repo = new RepositorioMySQL(conectar());
    // El repositorio obtiene precios y stock bajo bloqueo y delega las reglas al servicio.
    $resultado = $repo->registrarVenta($entrada['cliente'], $entrada['items']);
    unset($_SESSION['borrador_venta']);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
} catch (InvalidArgumentException $e) {
    if (http_response_code() !== 405) http_response_code(422);
    $error = $e->getMessage();
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(503);
    $error = 'No se pudo registrar la venta. Intente nuevamente';
}
function escapar(string $valor): string { return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); }
function soles(float $valor): string { return 'S/ ' . number_format($valor, 2, ',', ' '); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Resultado de venta · Doña Rosa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><h1>Resultado de la venta</h1>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= escapar($error) ?></div>
<?php else: ?><div class="alert alert-success" role="status">Venta #<?= (int) $resultado['venta_id'] ?> registrada correctamente.</div>
<p>Cliente: <?= escapar($resultado['cliente']) ?></p><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th>Descuento</th><th>Neto</th></tr></thead><tbody>
<?php foreach ($resultado['items'] as $item): ?><tr><td><?= escapar($item['nombre']) ?></td><td><?= $item['cantidad'] ?></td><td><?= soles((float) $item['precio_unitario']) ?></td><td><?= soles($item['subtotal']) ?></td><td><?= soles($item['descuento']) ?></td><td><?= soles($item['importe_neto']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><p>Subtotal de venta: <?= soles($resultado['subtotal']) ?> · Descuento de venta: <?= soles($resultado['descuento']) ?></p><p class="fs-4">Total: <strong><?= soles($resultado['total']) ?></strong></p><?php endif; ?>
<div class="d-flex gap-2"><a class="btn btn-primary" href="venta.php"><?= $error ? 'Corregir venta' : 'Nueva venta' ?></a><a class="btn btn-outline-secondary" href="index.php">Ver stock</a></div></main></body></html>
