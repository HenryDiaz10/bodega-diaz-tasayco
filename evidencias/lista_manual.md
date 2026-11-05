# Lista de cotejo manual S11

Pendiente de ejecución visual por el estudiante. Los resultados automatizados HTTP están en tests/resultados_8.md y no reemplazan esta actividad.

| N.° | Acción exacta en pantalla | Dato de entrada | Resultado esperado | Resultado manual |
|---|---|---|---|---|
| 1 | Registrar un producto de prueba, abrir Nueva venta, seleccionarlo y confirmar | Precio 2,50; stock 5; cantidad 5 | R1 y R4: venta válida, total 12,50 y stock final 0 | Pendiente |
| 2 | Abrir Nueva venta y seleccionar un producto con stock 5; confirmar cantidad 6 | Cantidad 6 | R1: error legible y stock sin cambios | Pendiente |
| 3 | Seleccionar arroz de precio 2,50 y stock al menos 40; indicar cantidad 40 | Subtotal 100,00 | R2: descuento 0,00; total 100,00 | Pendiente |
| 4 | Registrar un producto temporal a 100,01 con stock 1 y vender una unidad | Subtotal 100,01 | R2: descuento 5,00; total 95,01 | Pendiente |
| 5 | Seleccionar un producto y escribir cero en Cantidad; intentar confirmar | Cantidad 0 | R3: rechazo, sin venta ni cambios de stock | Pendiente |
| 6 | Seleccionar un producto y escribir menos uno en Cantidad; intentar confirmar | Cantidad -1 | R3: rechazo, sin venta ni cambios de stock | Pendiente |
| 7 | Agregar dos líneas del mismo producto con stock inicial 5 | Cantidades 3 y 3 | R1: rechazo por stock acumulado, sin venta ni descuentos de inventario | Pendiente |
| 8 | Agregar dos líneas de un producto de precio 12,00 y stock al menos 10; confirmar | Cantidad 5 en cada línea; subtotal conjunto 120,00 | R2 y R4: descuento global 6,00; total 114,00; stock disminuye 10 | Pendiente |

Pruebas manuales fallidas: completar después de ejecutar, sin asumir cero a partir de los tests HTTP. Use productos de prueba para no modificar existencias reales.
