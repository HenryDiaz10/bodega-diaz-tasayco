<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/ServicioVenta.php';

final class ServicioVentaTest extends TestCase
{
    public function testProductoInvalidoDevuelveUnErrorPorCampo(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $resultado = ServicioVenta::validarProducto([
                'nombre' => $i % 2 ? '' : str_repeat('ñ', 101 + $i),
                'precio_unitario' => $i % 2 ? '0' : '100000.00',
                'stock' => (string) (10000 + $i),
            ]);
            self::assertCount(3, $resultado['errores']);
        }
        self::assertSame([], ServicioVenta::validarProducto(['nombre' => str_repeat('ñ', 100), 'precio_unitario' => '99999,99', 'stock' => '9999'])['errores']);
        self::assertSame([], ServicioVenta::validarProducto(['nombre' => 'Arroz', 'precio_unitario' => '0.01', 'stock' => '0'])['errores']);
    }

    public function testCantidadesInvalidasSonRechazadasAntesDeCalcular(): void
    {
        foreach (['0', '-1', '1.5', '1e2', '10000'] as $cantidad) {
            for ($i = 0; $i < 100; $i++) {
                try {
                    ServicioVenta::validarEntrada('Cliente', [['producto_id' => '1', 'cantidad' => $cantidad]], true);
                    self::fail('Cantidad inválida aceptada');
                } catch (InvalidArgumentException $e) {
                    self::assertSame('La cantidad debe ser un número entero entre 1 y 9.999', $e->getMessage());
                }
            }
        }
    }

    public function testStockInsuficienteYStockExacto(): void
    {
        for ($stock = 0; $stock < 100; $stock++) {
            self::assertFalse(ServicioVenta::hayStock($stock + 1, $stock));
            if ($stock > 0) self::assertTrue(ServicioVenta::hayStock($stock, $stock));
        }
    }

    public function testSubtotalEsPrecioPorCantidad(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            $centimos = $i * 37;
            self::assertEqualsWithDelta($centimos * $i / 100,
                ServicioVenta::calcularSubtotal([['precio_unitario' => $centimos / 100, 'cantidad' => $i]]), 0.00001);
        }
    }

    public function testDescuentoRespetaFronteraYRedondeo(): void
    {
        for ($centimos = 9950; $centimos < 10150; $centimos++) {
            $esperado = $centimos > 10000 ? intdiv($centimos * 5 + 50, 100) / 100 : 0.0;
            self::assertEqualsWithDelta($esperado, ServicioVenta::calcularDescuento($centimos / 100), 0.00001);
        }
    }

    public function testDescuentaSobreTodaLaVentaAunqueCadaLineaNoSupere100(): void
    {
        $productos = [1 => ['nombre' => 'Arroz', 'precio_unitario' => '12.00', 'stock' => 10]];
        $venta = ServicioVenta::prepararVenta([['producto_id' => 1, 'cantidad' => 5], ['producto_id' => 1, 'cantidad' => 5]], $productos);
        self::assertEqualsWithDelta(120.0, $venta['subtotal'], 0.001);
        self::assertEqualsWithDelta(6.0, $venta['descuento'], 0.001);
        self::assertEqualsWithDelta(114.0, $venta['total'], 0.001);
        self::assertEqualsWithDelta(3.0, $venta['items'][0]['descuento'], 0.001);
        self::assertEqualsWithDelta(3.0, $venta['items'][1]['descuento'], 0.001);
        self::assertSame(10, $productos[1]['stock']);
    }

    public function testDescuentoCeroCuandoSubtotalEs99Con99(): void
    { self::assertEqualsWithDelta(0.0, ServicioVenta::calcularDescuento(99.99), 0.001); }

    public function testDescuentoCeroCuandoSubtotalEsExactamente100(): void
    { self::assertEqualsWithDelta(0.0, ServicioVenta::calcularDescuento(100.00), 0.001); }

    public function testAplicaDescuentoCuandoSubtotalEs100Con01(): void
    { self::assertEqualsWithDelta(5.0, ServicioVenta::calcularDescuento(100.01), 0.001); }

    public function testDescuentoRedondeaMedioCentimoHaciaArriba(): void
    { self::assertEqualsWithDelta(5.01, ServicioVenta::calcularDescuento(100.10), 0.001); }

    public function testAceptaVentaUnaUnidadPorDebajoDelStock(): void
    { self::assertSame(true, ServicioVenta::hayStock(9, 10)); }

    public function testAceptaVentaIgualAlStock(): void
    { self::assertSame(true, ServicioVenta::hayStock(10, 10)); }

    public function testRechazaVentaUnaUnidadPorEncimaDelStock(): void
    { self::assertSame(false, ServicioVenta::hayStock(11, 10)); }

    public function testRechazaProductoSinStock(): void
    { self::assertSame(false, ServicioVenta::hayStock(1, 0)); }

    public function testRechazaCantidadNegativa(): void
    { self::assertSame(false, ServicioVenta::validarCantidad(-1)); }

    public function testRechazaCantidadCero(): void
    { self::assertSame(false, ServicioVenta::validarCantidad(0)); }

    public function testAceptaCantidadUno(): void
    { self::assertSame(true, ServicioVenta::validarCantidad(1)); }

    public function testAceptaCantidadMaxima9999(): void
    { self::assertSame(true, ServicioVenta::validarCantidad(9999)); }

    public function testRechazaCantidad10000(): void
    { self::assertSame(false, ServicioVenta::validarCantidad(10000)); }

    public function testSubtotalMultiplicaPrecioPorCantidad(): void
    { self::assertEqualsWithDelta(7.50, ServicioVenta::calcularSubtotal([['precio_unitario' => '2.50', 'cantidad' => 3]]), 0.001); }

    public function testTotal100NoTieneDescuento(): void
    { self::assertEqualsWithDelta(100.0, ServicioVenta::calcularTotal(100.0), 0.001); }

    public function testTotal100Con01AplicaDescuento(): void
    { self::assertEqualsWithDelta(95.01, ServicioVenta::calcularTotal(100.01), 0.001); }

    public function testRepartoDeCentimosNoPierdeDescuentoGlobal(): void
    {
        $venta = ServicioVenta::prepararVenta([['producto_id' => 1, 'cantidad' => 1], ['producto_id' => 2, 'cantidad' => 1]],
            [1 => ['nombre' => 'A', 'precio_unitario' => '50.00', 'stock' => 1], 2 => ['nombre' => 'B', 'precio_unitario' => '50.10', 'stock' => 1]]);
        self::assertEqualsWithDelta(5.01, $venta['descuento'], 0.001);
        self::assertEqualsWithDelta(95.09, $venta['total'], 0.001);
        self::assertEqualsWithDelta(2.50, $venta['items'][0]['descuento'], 0.001);
        self::assertEqualsWithDelta(2.51, $venta['items'][1]['descuento'], 0.001);
    }
}
