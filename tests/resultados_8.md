# Verificación de ocho casos

Fecha: 2026-11-05 17:37:08 (Lima).

Método: GET del formulario y POST HTTP real con sesión y CSRF; comprobación del HTML de respuesta y de venta, detalles y stock en la base local. No se verificó la interacción visual ni JavaScript porque el acceso al navegador no estuvo disponible.

| N.° | Caso | Resultado | Evidencia |
|---|---|---|---|
| 1 | Stock exacto | APROBADA | Venta exitosa; stock 5 → 0. |
| 2 | Una unidad más | APROBADA | Stock insuficiente: hay 5 unidades disponibles |
| 3 | Subtotal 100,00 | APROBADA | Subtotal 100,00; descuento 0,00; total 100,00. |
| 4 | Subtotal 100,01 | APROBADA | Subtotal 100,01; descuento 5,00; total 95,01. |
| 5 | Cantidad cero | APROBADA | La cantidad debe ser un número entero entre 1 y 9.999 |
| 6 | Cantidad negativa | APROBADA | La cantidad debe ser un número entero entre 1 y 9.999 |
| 7 | Stock acumulado | APROBADA | Stock insuficiente: hay 2 unidades disponibles |
| 8 | Venta de dos productos | APROBADA | Subtotal 118,40; descuento global 5,92; total 112,48; stocks 50 → 6 y 25 → 23. |

Los productos y ventas de prueba se eliminaron; los registros originales de las tres tablas coinciden con la captura previa. Los contadores AUTO_INCREMENT pueden haber avanzado. El precio 100,01 se configuró exclusivamente en un producto temporal.
