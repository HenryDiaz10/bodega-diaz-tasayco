# Prompts utilizados

## P4 Script de base de datos

**Dónde se usó:** chat del proyecto en VS Code, con design.md y sql/bodega.sql como archivos de referencia.

```text
Actúa como INGENIERO de sistemas SENIOR. Lee design.md. Genera O MODIFICA O MEJORA el archivo sql/bodega.sql para MySQL 8 (XAMPP) con:
- CREATE DATABASE bodega; USE bodega;
- Las tres tablas con claves primarias, foráneas y tipos exactos del diseño.
- Datos de prueba: 6 productos con nombres de una bodega peruana (arroz, aceite, leche, azúcar, atún, gaseosa) con precios entre S/ 2,50 y S/ 12,00 y stock entre 0 y 50. Uno de los productos debe tener stock 0.
No generes procedimientos almacenados ni triggers. Explica en dos líneas qué hiciste.
```

## P5 Código de la aplicación

**Dónde se usó:** chat del proyecto en VS Code, para los archivos de config/, src/ y las páginas PHP de la raíz.

```text
Lee design.md y sql/bodega.sql. Genera la aplicación PHP 8 sin framework con esta estructura exacta:
config/conexion.php   → función conectar(): PDO, leyendo host, usuario, clave y base desde config/env.php
config/env.php        → arreglo con los datos de conexión (localhost, root, sin clave, bodega)
src/ServicioVenta.php → clase con métodos estáticos: validarCantidad(int $cantidad): bool, hayStock(int $cantidad, int $stock): bool, calcularSubtotal(array $items): float, calcularDescuento(float $subtotal): float, calcularTotal(float $subtotal): float. Sin HTML, sin SQL, sin acceso a base de datos.
src/RepositorioMySQL.php → métodos listarProductos(), obtenerProducto($id), registrarVenta($cliente, $items) con transacción y sentencias preparadas.
index.php             → lista productos con stock.
venta.php             → formulario: cliente, producto (select), cantidad; permite agregar varias líneas.
guardar_venta.php     → recibe el POST, usa ServicioVenta para validar y calcular, usa RepositorioMySQL para guardar; muestra mensaje de éxito o error legible.
Aplica las reglas R1 a R4 de requirements.md exactamente. Usa Bootstrap desde CDN para la apariencia. Después de generar, explica en una lista qué archivo implementa cada regla.
```

## P6 Verificación manual guiada

**Dónde se usó:** chat del proyecto en VS Code, para solicitar la tabla de cotejo de ocho pruebas.

```text
Genera una lista de cotejo de 8 pruebas manuales para esta aplicación, una por fila, con columnas: N.°, acción exacta en pantalla, dato de entrada, resultado esperado según la regla R1–R4 que aplica. Incluye obligatoriamente: vender exactamente el stock disponible, vender uno más que el stock, subtotal de exactamente S/ 100,00, subtotal de S/ 100,01, cantidad 0 y cantidad -1. No ejecutes nada; solo la tabla.
```

## P7 Refactorización para testeabilidad

**Dónde se usó:** chat del proyecto en VS Code, sobre src/ServicioVenta.php, guardar_venta.php y src/RepositorioMySQL.php.

```text
Revisa src/ServicioVenta.php. Si algún cálculo o validación de las reglas R1–R4 está fuera de esa clase (en guardar_venta.php o en RepositorioMySQL.php), muévelo a ServicioVenta sin cambiar el comportamiento. ServicioVenta no debe tener echo, HTML, PDO ni variables de sesión. Al terminar, lista cada método con su firma y la regla que implementa, y confirma que la aplicación sigue funcionando igual.
```
