<?php
declare(strict_types=1);
session_start();
$mensajeProducto = $_SESSION['mensaje_producto'] ?? '';
unset($_SESSION['mensaje_producto']);
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/RepositorioMySQL.php';
$productos = [];
$error = '';
try {
    $productos = (new RepositorioMySQL(conectar()))->listarProductos();
} catch (Throwable $e) {
    error_log((string) $e);
    $error = 'No se pudo cargar el catálogo. Verifique que MySQL esté iniciado y la base bodega esté importada.';
    http_response_code(503);
}
function escapar(string $valor): string { return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); }
$disponibles = count(array_filter($productos, fn(array $p): bool => (int) $p['stock'] > 0));
$agotados = count($productos) - $disponibles;
$unidades = array_sum(array_column($productos, 'stock'));
$escalaStock = max(1, (int) ceil(max(array_merge([0], array_column($productos, 'stock'))) / 10) * 10);
$porcentajeDisponible = $productos ? (int) round($disponibles / count($productos) * 100) : 0;
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Productos · Bodega Doña Rosa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/catalogo.css" rel="stylesheet">
<link href="assets/graficos.css" rel="stylesheet"></head>
<body>
<header class="site-header"><div class="shell header-inner">
<a class="brand" href="index.php" aria-label="Bodega Doña Rosa, inicio"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 10h18l-2-6H5l-2 6Zm1 0v10h16V10M9 20v-6h6v6M3 10c0 3 4 3 4 0 0 3 5 3 5 0 0 3 5 3 5 0 0 3 4 3 4 0"/></svg></span><span>Doña Rosa<small>BODEGA DE BARRIO</small></span></a>
<nav aria-label="Navegación principal"><a class="nav-link active" href="index.php" aria-current="page">Productos</a><a class="nav-link" href="venta.php">Ventas</a></nav>
<span class="header-note">Tu bodega, en orden.</span>
</div></header>
<main class="shell main-content">
<div class="page-heading"><div><p class="eyebrow">EL DÍA A DÍA DE TU BODEGA</p><h1>Productos y stock<span>.</span></h1><p class="intro">Todo lo que tienes, listo para vender.</p></div><a class="new-sale" href="venta.php"><span aria-hidden="true">＋</span> Nueva venta <span aria-hidden="true">↗</span></a></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= escapar($error) ?></div><?php endif; ?>
<?php if ($mensajeProducto): ?><div class="alert alert-success" role="status"><?= escapar($mensajeProducto) ?></div><?php endif; ?>
<?php if (!$error): ?><section class="stock-summary" aria-label="Resumen de inventario">
<div class="summary-item"><span class="summary-icon" aria-hidden="true">▦</span><div><span class="summary-label">Productos en catálogo</span><strong><?= count($productos) ?><small> productos</small></strong></div></div>
<div class="summary-item"><span class="summary-icon units" aria-hidden="true">↗</span><div><span class="summary-label">Stock disponible</span><strong><?= (int) $unidades ?><small> unidades</small></strong></div></div>
<div class="summary-item"><span class="summary-icon empty" aria-hidden="true">!</span><div><span class="summary-label">Por reponer</span><strong><?= $agotados ?><small><?= $agotados === 1 ? ' producto agotado' : ' productos agotados' ?></small></strong></div></div>
</section><?php endif; ?>
<?php if (!$error && $productos): ?>
<section class="inventory-charts" aria-label="Gráficos del inventario actual">
<figure class="chart-card">
<figcaption class="chart-heading"><p class="eyebrow">INVENTARIO DE UN VISTAZO</p><h2>Stock por producto</h2><p>Unidades disponibles en este momento.</p></figcaption>
<div class="bar-chart">
<div class="bar-axis" aria-hidden="true"><span>0</span><span><?= $escalaStock / 2 ?></span><span><?= $escalaStock ?></span></div>
<?php foreach ($productos as $producto): $stock = (int) $producto['stock']; ?>
<div class="bar-row"><span class="bar-name" title="<?= escapar($producto['nombre']) ?>"><?= escapar($producto['nombre']) ?></span><div class="bar-track" aria-hidden="true"><span class="bar-fill <?= $stock === 0 ? 'zero' : '' ?>" style="width:<?= $stock === 0 ? '3px' : round($stock / $escalaStock * 100, 2) . '%' ?>"></span></div><span class="bar-value"><?= $stock ?><span class="visually-hidden"> unidades disponibles</span></span></div>
<?php endforeach; ?>
</div><p class="chart-footnote"><i class="legend-dot" aria-hidden="true"></i> Stock actual <i class="legend-dot orange" aria-hidden="true"></i> Agotado</p></figure>
<figure class="chart-card availability-card">
<figcaption class="chart-heading"><p class="eyebrow">DISPONIBILIDAD</p><h2>Listos para vender</h2><p>Proporción de productos con stock.</p></figcaption>
<div class="donut-wrap"><div class="stock-donut" style="--available:<?= $porcentajeDisponible ?>%" role="img" aria-label="<?= $disponibles ?> de <?= count($productos) ?> productos disponibles; <?= $agotados ?> agotados"><div class="donut-center"><strong><?= $porcentajeDisponible ?><small>%</small></strong><span>con stock</span></div></div></div>
<div class="donut-legend"><div><span><i class="legend-dot" aria-hidden="true"></i>Disponibles</span><strong><?= $disponibles ?> productos</strong></div><div><span><i class="legend-dot orange" aria-hidden="true"></i>Agotados</span><strong><?= $agotados ?> <?= $agotados === 1 ? 'producto' : 'productos' ?></strong></div></div>
<p class="chart-footnote"><?= $agotados ? 'Revisa los agotados para planificar la reposición.' : 'Todos tus productos tienen stock disponible.' ?></p></figure>
</section>
<?php endif; ?>
<section class="catalog-card" aria-labelledby="catalog-title">
<div class="catalog-toolbar"><div><h2 id="catalog-title">Tu catálogo <span><?= count($productos) ?></span></h2><p>Consulta precios y disponibilidad al instante.</p><a href="producto_nuevo.php" class="text-success d-inline-block mt-2 small fw-semibold">＋ Registrar producto</a></div>
<div class="search-wrap"><label for="buscar" class="visually-hidden">Buscar producto</label><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg><input id="buscar" type="search" placeholder="Buscar un producto…" autocomplete="off" aria-controls="catalogo"></div></div>
<div class="filter-bar" role="group" aria-label="Filtrar por disponibilidad"><button class="filter active" type="button" data-filtro="todos" aria-pressed="true">Todos <span><?= count($productos) ?></span></button><button class="filter" type="button" data-filtro="disponibles" aria-pressed="false">Disponibles <span><?= $disponibles ?></span></button><button class="filter" type="button" data-filtro="agotados" aria-pressed="false">Agotados <span><?= $agotados ?></span></button></div>
<div class="table-responsive"><table id="catalogo"><caption class="visually-hidden">Catálogo de productos, precios en soles y stock actual.</caption><thead><tr><th scope="col">PRODUCTO</th><th scope="col">PRECIO UNITARIO</th><th scope="col">DISPONIBILIDAD</th><th scope="col"><span class="visually-hidden">Acciones</span></th></tr></thead><tbody>
<?php foreach ($productos as $producto): ?>
<tr data-stock="<?= (int) $producto['stock'] ?>" data-nombre="<?= escapar($producto['nombre']) ?>"><td><div class="product-cell"><span class="product-icon <?= (int) $producto['stock'] === 0 ? 'muted' : '' ?>" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Zm-8 4.5 8 4.5 8-4.5M12 12v9M8 5.25l8 4.5"/></svg></span><div><span class="product-name"><?= escapar($producto['nombre']) ?></span><small class="product-ref">PRD-<?= str_pad((string) $producto['id'], 3, '0', STR_PAD_LEFT) ?></small></div></div></td><td class="price"><span>S/</span> <?= number_format((float) $producto['precio_unitario'], 2, ',', ' ') ?></td>
<td><span class="stock-pill <?= (int) $producto['stock'] === 0 ? 'out-of-stock' : '' ?>"><span class="status-dot" aria-hidden="true"></span><?= (int) $producto['stock'] === 0 ? 'Agotado' : (int) $producto['stock'] . ' unidades' ?></span><?php if ((int) $producto['stock'] === 0): ?><small class="replenish">Pendiente de reposición</small><?php endif; ?></td><td><a class="text-success small" href="producto_editar.php?id=<?= (int) $producto['id'] ?>" aria-label="Editar <?= escapar($producto['nombre']) ?>">Editar</a></td></tr>
<?php endforeach; ?>
<?php if (!$productos && !$error): ?><tr><td colspan="4">No hay productos registrados.</td></tr><?php endif; ?>
</tbody></table></div><p id="sin-resultados" class="no-results" role="status" hidden>No se encontraron productos para esta búsqueda.</p>
<div class="catalog-footer"><span id="conteo" aria-live="polite"><?= count($productos) ?> productos en el catálogo</span><span>Precios expresados en soles (S/)</span></div></section>
<footer class="page-footer"><span><span aria-hidden="true">ⓘ</span> El stock se verifica nuevamente al confirmar cada venta.</span><span>Bodega Doña Rosa</span></footer></main>
<script>
'use strict';
const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
let filtro = 'todos';
function filtrar() {
    const consulta = normalizar(document.getElementById('buscar').value.trim());
    let visibles = 0;
    document.querySelectorAll('#catalogo tbody tr[data-nombre]').forEach(fila => {
        const disponible = Number(fila.dataset.stock) > 0;
        const coincide = filtro === 'todos' || (filtro === 'disponibles' ? disponible : !disponible);
        fila.hidden = !coincide || !normalizar(fila.dataset.nombre).includes(consulta);
        if (!fila.hidden) visibles++;
    });
    document.getElementById('sin-resultados').hidden = visibles > 0;
    document.getElementById('conteo').textContent = `${visibles} de <?= count($productos) ?> productos`;
}
document.getElementById('buscar').addEventListener('input', filtrar);
document.querySelectorAll('[data-filtro]').forEach(boton => boton.addEventListener('click', () => {
    filtro = boton.dataset.filtro;
    document.querySelectorAll('[data-filtro]').forEach(opcion => {
        const activo = opcion === boton;
        opcion.classList.toggle('active', activo);
        opcion.setAttribute('aria-pressed', String(activo));
    });
    filtrar();
}));
</script></body></html>
