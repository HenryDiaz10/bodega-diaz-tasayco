<?php
declare(strict_types=1);

final class ServicioVenta
{
    /** Requisitos 1 y 2: devuelve todos los errores sin acceder a persistencia. */
    public static function validarProducto(array $entrada): array
    {
        $errores = [];
        $nombre = is_string($entrada['nombre'] ?? null) ? trim($entrada['nombre']) : '';
        $precio = is_string($entrada['precio_unitario'] ?? null) ? trim($entrada['precio_unitario']) : '';
        $stock = is_string($entrada['stock'] ?? null) ? trim($entrada['stock']) : '';
        if (preg_match('/^.{1,100}$/us', $nombre) !== 1) {
            $errores['nombre'] = 'El nombre debe tener entre 1 y 100 caracteres.';
        }
        // Acepta punto o coma decimal; nunca separadores de miles ni exponentes.
        $precio = str_replace(',', '.', $precio);
        if (!preg_match('/^[0-9]{1,5}(?:\.[0-9]{1,2})?$/D', $precio)
            || (float) $precio < 0.01 || (float) $precio > 99999.99) {
            $errores['precio_unitario'] = 'El precio debe estar entre S/ 0,01 y S/ 99.999,99, con hasta dos decimales.';
        }
        if (!preg_match('/^[0-9]{1,4}$/D', $stock)) {
            $errores['stock'] = 'El stock debe ser un número entero entre 0 y 9.999.';
        }
        return ['errores' => $errores, 'datos' => [
            'nombre' => $nombre,
            'precio_unitario' => isset($errores['precio_unitario']) ? $precio : number_format((float) $precio, 2, '.', ''),
            'stock' => isset($errores['stock']) ? $stock : (int) $stock,
        ]];
    }

    /** Valida datos de negocio y normaliza el texto recibido por el formulario. */
    public static function validarEntrada(mixed $cliente, mixed $items, bool $desdeFormulario = false): array
    {
        if (!is_string($cliente) || trim($cliente) === '' || preg_match('/^.{1,100}$/us', trim($cliente)) !== 1) {
            throw new InvalidArgumentException('Ingrese un cliente de hasta 100 caracteres.');
        }
        if (!is_array($items) || !$items) {
            throw new InvalidArgumentException('Debe agregar al menos un producto a la venta');
        }
        $normalizados = [];
        foreach ($items as $item) {
            if ($desdeFormulario) {
                // No convertir antes de validar: un cast truncaría los decimales.
                $cantidad = is_array($item) ? ($item['cantidad'] ?? null) : null;
                if (!is_string($cantidad) || !preg_match('/^[0-9]{1,4}$/D', $cantidad)
                    || !self::validarCantidad((int) $cantidad)) {
                    throw new InvalidArgumentException('La cantidad debe ser un número entero entre 1 y 9.999');
                }
                $id = $item['producto_id'] ?? null;
                if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10 || (int) $id < 1 || (int) $id > 4294967295) {
                    throw new InvalidArgumentException('Seleccione un producto válido.');
                }
                $normalizados[] = ['producto_id' => (int) $id, 'cantidad' => (int) $cantidad];
            } else {
                if (!is_array($item) || !isset($item['cantidad'], $item['producto_id'])
                    || !is_int($item['cantidad']) || !self::validarCantidad($item['cantidad'])
                    || !is_int($item['producto_id']) || $item['producto_id'] < 1) {
                    throw new InvalidArgumentException('La cantidad debe ser un número entero entre 1 y 9.999');
                }
                $normalizados[] = $item;
            }
        }
        return ['cliente' => trim($cliente), 'items' => $normalizados];
    }

    /** Productos indexados por ID; recibe una copia del stock, sin efectos externos. */
    public static function prepararVenta(array $items, array $productos): array
    {
        // También protege las llamadas directas al servicio, sin controlador.
        $items = self::validarEntrada('Venta', $items)['items'];
        $detalles = [];
        $subtotalCentimos = 0;
        foreach ($items as $item) {
            if (!isset($productos[$item['producto_id']])) {
                throw new InvalidArgumentException('El producto seleccionado ya no existe.');
            }
            $producto = &$productos[$item['producto_id']];
            if (!self::hayStock($item['cantidad'], (int) $producto['stock'])) {
                throw new InvalidArgumentException('Stock insuficiente: hay ' . $producto['stock'] . ' unidades disponibles');
            }
            $producto['stock'] -= $item['cantidad'];
            $item['nombre'] = $producto['nombre'];
            $item['precio_unitario'] = $producto['precio_unitario'];
            $item['subtotal'] = self::calcularSubtotal([$item]);
            $subtotalCentimos += (int) round($item['subtotal'] * 100);
            if ($subtotalCentimos > 9999999999) {
                throw new InvalidArgumentException('El total de la venta supera el importe máximo permitido de S/ 99 999 999,99.');
            }
            $detalles[] = $item;
            unset($producto);
        }
        $subtotal = $subtotalCentimos / 100;
        $descuento = self::calcularDescuento($subtotal);
        $descuentoCentimos = (int) round($descuento * 100);
        $acumulado = 0;
        $repartido = 0;
        // Reparto acumulado: la suma de descuentos de detalles coincide al céntimo con R2.
        foreach ($detalles as &$detalle) {
            $acumulado += (int) round($detalle['subtotal'] * 100);
            $objetivo = (int) round($acumulado * ($descuentoCentimos / max(1, $subtotalCentimos)), 0, PHP_ROUND_HALF_UP);
            $detalle['descuento'] = ($objetivo - $repartido) / 100;
            $detalle['importe_neto'] = round($detalle['subtotal'] - $detalle['descuento'], 2);
            $repartido = $objetivo;
        }
        unset($detalle);
        return ['items' => $detalles, 'subtotal' => (float) $subtotal,
            'descuento' => $descuento, 'total' => self::calcularTotal($subtotal)];
    }

    public static function validarCantidad(int $cantidad): bool
    {
        return $cantidad >= 1 && $cantidad <= 9999;
    }

    public static function hayStock(int $cantidad, int $stock): bool
    {
        return self::validarCantidad($cantidad) && $cantidad <= $stock;
    }

    /** Cada elemento contiene precio_unitario y cantidad. */
    public static function calcularSubtotal(array $items): float
    {
        $centimos = 0;
        foreach ($items as $item) {
            $centimos += (int) round((float) $item['precio_unitario'] * 100, 0, PHP_ROUND_HALF_UP)
                * (int) $item['cantidad'];
        }
        return $centimos / 100;
    }

    /** R2 de la guía: descuento sobre el subtotal de toda la venta. */
    public static function calcularDescuento(float $subtotal): float
    {
        return $subtotal > 100 ? round($subtotal * 0.05, 2, PHP_ROUND_HALF_UP) : 0.0;
    }

    /** Importe final de la venta después del descuento global. */
    public static function calcularTotal(float $subtotal): float
    {
        return round($subtotal - self::calcularDescuento($subtotal), 2, PHP_ROUND_HALF_UP);
    }
}
