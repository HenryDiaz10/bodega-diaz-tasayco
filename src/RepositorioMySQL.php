<?php
declare(strict_types=1);
require_once __DIR__ . '/ServicioVenta.php';

final class RepositorioMySQL
{
    public function __construct(private PDO $pdo) {}

    public function listarProductos(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, precio_unitario, stock FROM producto ORDER BY nombre');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerProducto($id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, precio_unitario, stock FROM producto WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Recibe datos ya validados por ServicioVenta. */
    public function guardarProducto(array $datos, ?int $id = null): int
    {
        try {
            if ($id === null) {
                $stmt = $this->pdo->prepare('INSERT INTO producto (nombre, precio_unitario, stock) VALUES (?, ?, ?)');
                $stmt->execute([$datos['nombre'], $datos['precio_unitario'], $datos['stock']]);
                return (int) $this->pdo->lastInsertId();
            }
            $stmt = $this->pdo->prepare('UPDATE producto SET nombre = ?, precio_unitario = ?, stock = ? WHERE id = ?');
            $stmt->execute([$datos['nombre'], $datos['precio_unitario'], $datos['stock'], $id]);
            if ($stmt->rowCount() === 0 && $this->obtenerProducto($id) === null) {
                throw new InvalidArgumentException('El producto seleccionado ya no existe.');
            }
            return $id;
        } catch (PDOException $e) {
            error_log((string) $e);
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new InvalidArgumentException('El nombre del producto ya existe', 0, $e);
            }
            throw new RuntimeException('No se pudo guardar el producto. Intente nuevamente', 0, $e);
        }
    }

    /** Recibe producto_id y cantidad; vuelve a obtener precios y stock bajo bloqueo. */
    public function registrarVenta($cliente, $items): array
    {
        $entrada = ServicioVenta::validarEntrada($cliente, $items);
        $cliente = $entrada['cliente'];
        $items = $entrada['items'];
        try {
            $this->pdo->beginTransaction();
            // Orden estable de bloqueos para reducir interbloqueos entre ventas.
            $ids = array_unique(array_column($items, 'producto_id'));
            sort($ids, SORT_NUMERIC);
            $buscar = $this->pdo->prepare('SELECT id, nombre, precio_unitario, stock FROM producto WHERE id = ? FOR UPDATE');
            $productos = [];
            foreach ($ids as $id) {
                $buscar->execute([$id]);
                $producto = $buscar->fetch();
                $productos[$id] = $producto ?: null;
            }
            $preparada = ServicioVenta::prepararVenta($items, $productos);
            $detalles = $preparada['items'];
            $total = $preparada['total'];
            $insertar = $this->pdo->prepare('INSERT INTO venta (cliente, total) VALUES (?, ?)');
            $insertar->execute([trim($cliente), number_format($total, 2, '.', '')]);
            $ventaId = (int) $this->pdo->lastInsertId();
            $detalle = $this->pdo->prepare('INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal, descuento) VALUES (?, ?, ?, ?, ?, ?)');
            $stock = $this->pdo->prepare('UPDATE producto SET stock = stock - ? WHERE id = ? AND stock >= ?');
            foreach ($detalles as $item) {
                $detalle->execute([$ventaId, $item['producto_id'], $item['cantidad'], $item['precio_unitario'],
                    number_format($item['subtotal'], 2, '.', ''), number_format($item['descuento'], 2, '.', '')]);
                $stock->execute([$item['cantidad'], $item['producto_id'], $item['cantidad']]);
                if ($stock->rowCount() !== 1) {
                    throw new RuntimeException('No se pudo actualizar el stock.');
                }
            }
            $this->pdo->commit();
            return ['venta_id' => $ventaId, 'cliente' => trim($cliente), 'subtotal' => $preparada['subtotal'],
                'descuento' => $preparada['descuento'], 'total' => $total, 'items' => $detalles];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e instanceof InvalidArgumentException) {
                throw $e;
            }
            error_log((string) $e);
            throw new RuntimeException('No se pudo registrar la venta. Intente nuevamente', 0, $e);
        }
    }
}
