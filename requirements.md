# Requisitos de Bodega Doña Rosa

Fuente funcional: D0.md y guía de laboratorio 11. R2 se aplica al subtotal de toda la venta. Este documento sustituye el criterio anterior por línea.

## 1 Registrar productos
Como cajero quiero registrar productos para mantener disponible el catálogo de la bodega.

- WHEN se envía nombre de 1 a 100 caracteres, precio entre 0,01 y 99.999,99 y stock entero entre 0 y 9.999, THE SYSTEM SHALL guardar el producto y mostrar «Producto guardado correctamente».
- IF el nombre ya existe, THE SYSTEM SHALL mostrar «El nombre del producto ya existe» junto a Nombre sin guardar cambios.
- IF hay campos inválidos, THE SYSTEM SHALL mostrar un error junto a cada campo y conservar los datos ingresados sin consultar al repositorio para guardar.
- WHEN se abre el catálogo, THE SYSTEM SHALL mostrar nombre, precio y stock de todos los productos.

## 2 Editar productos
Como cajero quiero editar nombre, precio y stock para corregir y reponer el inventario.

- WHEN se guardan datos válidos de un producto existente, THE SYSTEM SHALL actualizarlo y mostrar «Producto actualizado correctamente».
- IF el nombre pertenece a otro producto, THE SYSTEM SHALL rechazar el cambio y mostrar el error junto a Nombre.
- IF cualquier campo es inválido, THE SYSTEM SHALL mostrar todos los errores de campo sin modificar el producto.

## 3 Preparar una venta
Como cajero quiero agregar uno o más productos para armar el pedido correctamente.

- IF la cantidad no es un entero entre 1 y 9.999, THE SYSTEM SHALL rechazarla con «La cantidad debe ser un número entero entre 1 y 9.999». (R3)
- IF la cantidad acumulada del mismo producto supera su stock, THE SYSTEM SHALL rechazar la venta con «Stock insuficiente: hay N unidades disponibles». (R1)
- WHEN la cantidad es exactamente el stock, THE SYSTEM SHALL aceptar la línea. (R1)
- WHEN se selecciona un producto válido, THE SYSTEM SHALL obtener su precio del catálogo y calcular subtotal como precio por cantidad.

## 4 Calcular importes
Como cajero quiero conocer el descuento y total para cobrar el importe correcto.

- WHEN el subtotal de toda la venta supera S/ 100,00, THE SYSTEM SHALL calcular un descuento del 5 %, redondeado a dos decimales con mitad hacia arriba; para subtotal menor o igual a S/ 100,00 será cero. (R2)
- WHEN hay varias líneas, THE SYSTEM SHALL sumar primero sus subtotales y aplicar R2 una sola vez; dos líneas de S/ 60,00 generan S/ 6,00 de descuento y total S/ 114,00. (R2)
- THE SYSTEM SHALL mostrar subtotal global, descuento global y total; por compatibilidad con el esquema existente distribuirá el descuento entre detalles sin perder céntimos.

## 5 Confirmar venta
Como cajero quiero confirmar la venta para registrarla y actualizar el inventario de forma consistente.

- IF la venta no tiene líneas, THE SYSTEM SHALL rechazarla con «Debe agregar al menos un producto a la venta».
- WHEN se confirma una venta válida, THE SYSTEM SHALL guardar venta y detalles y reducir el stock en una misma transacción con bloqueo de los productos. (R4)
- IF falla la persistencia, THE SYSTEM SHALL revertir todos los cambios y mostrar «No se pudo registrar la venta. Intente nuevamente». (R4)
- WHEN se confirma correctamente, THE SYSTEM SHALL mostrar el resumen y dejar disponible el stock actualizado.

## Límites de alcance
Sin autenticación, reportes ni facturación electrónica. Los gráficos del catálogo muestran solo el inventario actual. Se conservan los límites de campos del diseño existente para no truncar registros.
