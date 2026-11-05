<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/RepositorioMySQL.php';
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$borrador = $_SESSION['borrador_venta'] ?? ['cliente' => '', 'items' => []];
$productos = [];
$error = '';
try {
    $productos = (new RepositorioMySQL(conectar()))->listarProductos();
} catch (Throwable $e) {
    error_log((string) $e);
    $error = 'No se pudo cargar el catálogo. Verifique la conexión con MySQL.';
    http_response_code(503);
}
function escapar(string $valor): string { return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); }
$hayDisponibles = (bool) array_filter($productos, fn(array $p): bool => (int) $p['stock'] > 0);
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Nueva venta · Doña Rosa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/catalogo.css" rel="stylesheet"></head>
<body><main class="shell main-content"><a class="text-success" href="index.php">← Ver productos</a><div class="page-heading mt-4"><div><p class="eyebrow">VENTAS DE LA BODEGA</p><h1>Nueva venta<span>.</span></h1><p class="intro">Arma el pedido y revisa los importes antes de confirmar.</p></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= escapar($error) ?></div><?php endif; ?>
<p>Descuento del 5 % cuando el subtotal de toda la venta supera S/ 100,00.</p>
<?php if (!$error && !$hayDisponibles): ?><div class="alert alert-warning" role="status">No hay productos con stock disponible para vender.</div><?php endif; ?>
<form method="post" action="guardar_venta.php" class="catalog-card p-4" id="venta">
<input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
<label for="cliente" class="form-label">Cliente</label><input id="cliente" name="cliente" class="form-control mb-4" maxlength="100" required autocomplete="name" value="<?= escapar($borrador['cliente']) ?>">
<div id="lineas"></div><div id="aviso" class="text-danger mb-3" role="alert" aria-live="polite"></div>
<div class="d-flex flex-wrap gap-2"><button type="button" id="agregar" class="btn btn-outline-primary" <?= !$hayDisponibles ? 'disabled' : '' ?>>Agregar línea</button>
<button class="btn btn-primary" <?= !$hayDisponibles ? 'disabled' : '' ?>>Confirmar venta</button></div>
<div class="mt-4" aria-live="polite"><p>Subtotal: <span id="subtotal-venta">S/ 0,00</span> · Descuento: <span id="descuento-venta">S/ 0,00</span></p><p class="fs-5 mb-0">Total: <strong id="total">S/ 0,00</strong></p></div>
<noscript><p class="text-danger">Active JavaScript para agregar las líneas de la venta.</p></noscript>
</form>
<template id="plantilla"><fieldset class="border rounded p-3 mb-3 linea"><legend class="float-none w-auto fs-6 px-2">Producto de la venta</legend>
<div class="row g-3 align-items-end"><div class="col-md-6"><label class="form-label">Producto<select class="form-select producto" required><option value="">Seleccione un producto</option>
<?php foreach ($productos as $producto): ?><option value="<?= (int) $producto['id'] ?>" data-precio="<?= escapar($producto['precio_unitario']) ?>" data-stock="<?= (int) $producto['stock'] ?>" <?= (int) $producto['stock'] === 0 ? 'disabled' : '' ?>><?= escapar($producto['nombre']) ?> — S/ <?= number_format((float) $producto['precio_unitario'], 2, ',', ' ') ?> (stock: <?= (int) $producto['stock'] ?>)</option><?php endforeach; ?>
</select></label></div><div class="col-md-3"><label class="form-label">Cantidad<input class="form-control cantidad" type="number" min="1" max="9999" step="1" value="1" required></label></div>
<div class="col-md-3"><button type="button" class="btn btn-outline-danger quitar">Quitar línea</button></div></div>
<p class="resumen mt-3 mb-0" aria-live="polite">Seleccione un producto.</p></fieldset></template>
</main><script>
'use strict';
const lineas = document.getElementById('lineas');
const borrador = <?= json_encode(array_values(array_filter($borrador['items'], 'is_array')), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
const dinero = centimos => 'S/ ' + (centimos / 100).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
let siguiente = 0;
function recalcular() {
    let total = 0;
    const detalles = [];
    const cantidades = new Map();
    let aviso = '';
    lineas.querySelectorAll('.linea').forEach(linea => {
        const select = linea.querySelector('.producto');
        const opcion = select.selectedOptions[0];
        const entrada = linea.querySelector('.cantidad');
        const cantidad = Number(entrada.value);
        entrada.setCustomValidity('');
        if (!/^[0-9]{1,4}$/.test(entrada.value) || !Number.isInteger(cantidad) || cantidad < 1 || cantidad > 9999) {
            entrada.setCustomValidity('La cantidad debe ser un número entero entre 1 y 9.999');
            linea.querySelector('.resumen').textContent = 'La cantidad debe ser un número entero entre 1 y 9.999';
            return;
        }
        if (!select.value) { linea.querySelector('.resumen').textContent = 'Seleccione un producto.'; return; }
        const acumulada = (cantidades.get(select.value) || 0) + cantidad;
        cantidades.set(select.value, acumulada);
        if (acumulada > Number(opcion.dataset.stock)) {
            const disponible = Math.max(0, Number(opcion.dataset.stock) - (acumulada - cantidad));
            aviso = 'Stock insuficiente: hay ' + disponible + ' unidades disponibles';
            entrada.setCustomValidity(aviso);
        }
        const subtotal = Math.round(Number(opcion.dataset.precio) * 100) * cantidad;
        total += subtotal;
        detalles.push({linea, subtotal});
    });
    const descuento = total > 10000 ? Math.floor((total * 5 + 50) / 100) : 0;
    let acumulado = 0;
    let repartido = 0;
    detalles.forEach(({linea, subtotal}) => {
        acumulado += subtotal;
        const objetivo = Math.round(acumulado * (descuento / Math.max(1, total)));
        const parte = objetivo - repartido;
        repartido = objetivo;
        linea.querySelector('.resumen').textContent = `Subtotal: ${dinero(subtotal)} · Parte del descuento de venta: ${dinero(parte)} · Neto: ${dinero(subtotal - parte)}`;
    });
    document.getElementById('subtotal-venta').textContent = dinero(total);
    document.getElementById('descuento-venta').textContent = dinero(descuento);
    document.getElementById('total').textContent = dinero(total - descuento);
    document.getElementById('aviso').textContent = aviso;
}
document.getElementById('agregar').addEventListener('click', () => {
    const fragmento = document.getElementById('plantilla').content.cloneNode(true);
    fragmento.querySelector('.producto').name = `items[${siguiente}][producto_id]`;
    fragmento.querySelector('.cantidad').name = `items[${siguiente}][cantidad]`;
    siguiente++;
    lineas.append(fragmento);
    recalcular();
    lineas.lastElementChild.querySelector('.producto').focus();
});
lineas.addEventListener('input', recalcular);
lineas.addEventListener('change', recalcular);
lineas.addEventListener('click', evento => {
    if (evento.target.classList.contains('quitar')) { evento.target.closest('.linea').remove(); recalcular(); }
});
document.getElementById('venta').addEventListener('submit', evento => {
    recalcular();
    if (!lineas.children.length) { evento.preventDefault(); document.getElementById('aviso').textContent = 'Debe agregar al menos un producto a la venta'; }
    else if (!evento.target.reportValidity()) evento.preventDefault();
});
if (!document.getElementById('agregar').disabled) {
    if (borrador.length) {
        borrador.forEach(item => {
            document.getElementById('agregar').click();
            const linea = lineas.lastElementChild;
            const select = linea.querySelector('.producto');
            if (typeof item.producto_id === 'string') select.value = item.producto_id;
            if (typeof item.cantidad === 'string') linea.querySelector('.cantidad').value = item.cantidad;
        });
        recalcular();
    } else document.getElementById('agregar').click();
}
</script></body></html>
