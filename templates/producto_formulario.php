<?php
declare(strict_types=1);
if (!isset($editar) || !is_bool($editar)) {
    http_response_code(404);
    exit;
}
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../src/RepositorioMySQL.php';
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$errores = [];
$error = '';
$id = null;
$datos = ['nombre' => '', 'precio_unitario' => '', 'stock' => '0'];
$titulo = $editar ? 'Editar producto' : 'Nuevo producto';
$repo = null;
try {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        http_response_code(405);
        throw new RuntimeException('Abra el formulario para guardar un producto.');
    }
    if ($editar) {
        $valorId = $_GET['id'] ?? null;
        if (!is_string($valorId) || !ctype_digit($valorId) || strlen($valorId) > 10 || (int) $valorId < 1 || (int) $valorId > 4294967295) {
            http_response_code(404);
            throw new RuntimeException('El producto seleccionado no existe.');
        }
        $id = (int) $valorId;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos = [];
        foreach (['nombre', 'precio_unitario', 'stock'] as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            http_response_code(403);
            throw new RuntimeException('El formulario ha caducado. Recargue la página e intente nuevamente.');
        }
        $validacion = ServicioVenta::validarProducto($datos);
        $errores = $validacion['errores'];
        if (!$errores) {
            $repo = new RepositorioMySQL(conectar());
            try {
                $repo->guardarProducto($validacion['datos'], $id);
                $_SESSION['mensaje_producto'] = $editar ? 'Producto actualizado correctamente' : 'Producto guardado correctamente';
                header('Location: index.php', true, 303);
                exit;
            } catch (InvalidArgumentException $e) {
                if ($e->getMessage() === 'El nombre del producto ya existe') {
                    $errores['nombre'] = $e->getMessage();
                } else {
                    throw $e;
                }
            }
        }
        http_response_code(422);
    } elseif ($editar) {
        $repo = new RepositorioMySQL(conectar());
        $producto = $repo->obtenerProducto($id);
        if (!$producto) {
            http_response_code(404);
            throw new RuntimeException('El producto seleccionado no existe.');
        }
        $datos = $producto;
    }
} catch (Throwable $e) {
    error_log((string) $e);
    if (in_array(http_response_code(), [403, 404, 405], true)) {
        $error = $e->getMessage();
    } else {
        http_response_code(503);
        $error = 'No se pudo guardar o cargar el producto. Intente nuevamente';
    }
}
function escapar(string $valor): string { return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $titulo ?> · Doña Rosa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/catalogo.css" rel="stylesheet"></head>
<body><main class="shell main-content" style="max-width:720px"><a href="index.php" class="text-success">← Volver al catálogo</a><div class="page-heading mt-4"><div><p class="eyebrow">CATÁLOGO DE LA BODEGA</p><h1><?= $titulo ?><span>.</span></h1><p class="intro">Mantén tus precios y existencias al día.</p></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= escapar($error) ?></div><?php endif; ?>
<?php if ($errores): ?><div class="alert alert-warning" role="alert">Revisa los campos indicados. Tus datos se han conservado.</div><?php endif; ?>
<?php if (!$error || $errores): ?><form class="catalog-card p-4" method="post" action="<?= $editar ? 'producto_editar.php?id=' . $id : 'producto_nuevo.php' ?>" novalidate>
<input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
<?php foreach (['nombre' => 'Nombre', 'precio_unitario' => 'Precio unitario (S/)', 'stock' => 'Stock'] as $campo => $etiqueta): ?>
<div class="mb-4"><label for="<?= $campo ?>" class="form-label"><?= $etiqueta ?></label><input id="<?= $campo ?>" name="<?= $campo ?>" class="form-control <?= isset($errores[$campo]) ? 'is-invalid' : '' ?>" value="<?= escapar((string) $datos[$campo]) ?>" required <?= $campo === 'nombre' ? 'maxlength="100"' : ($campo === 'precio_unitario' ? 'inputmode="decimal"' : 'inputmode="numeric"') ?> aria-describedby="<?= $campo ?>-ayuda <?= $campo ?>-error" <?= isset($errores[$campo]) ? 'aria-invalid="true"' : '' ?>>
<div id="<?= $campo ?>-ayuda" class="form-text"><?= $campo === 'nombre' ? 'Entre 1 y 100 caracteres; no puede repetirse.' : ($campo === 'precio_unitario' ? 'De S/ 0,01 a S/ 99.999,99. Usa coma o punto decimal.' : 'Un número entero entre 0 y 9.999.') ?></div>
<div id="<?= $campo ?>-error" class="invalid-feedback"><?= escapar($errores[$campo] ?? '') ?></div></div>
<?php endforeach; ?><div class="d-flex gap-3 align-items-center"><button class="new-sale border-0" type="submit"><?= $editar ? 'Guardar cambios' : 'Guardar producto' ?></button><a href="index.php" class="text-secondary">Cancelar</a></div></form><?php endif; ?>
</main></body></html>
