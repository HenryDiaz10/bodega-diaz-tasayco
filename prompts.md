# Prompts de la guía S11

Textos tomados de la guía de laboratorio. El lugar indicado corresponde al uso previsto en la guía.

## P1 Documento base D0

**Dónde se utiliza:** Editor de texto; redacción manual de D0.md.

```text
# D0 · Registro de ventas de la bodega «Doña Rosa»
## Problema
(2 líneas: quién, qué le pasa, qué pierde)
## Usuarios
(quién usa la aplicación y en qué momento)
## Alcance de esta versión
(qué SÍ hace: registrar productos, registrar ventas, descontar stock, aplicar descuento)
(qué NO hace: usuarios, reportes, facturación electrónica)
## Reglas de negocio
R1, R2, R3, R4 copiadas literalmente de la guía.
## Restricciones técnicas
PHP 8 + MySQL sobre XAMPP · sin framework · sin librerías externas salvo PHPUnit.
```

## P2 Requisitos

**Dónde se utiliza:** Kiro, chat en modo spec.

```text
Actúa como analista de sistemas. A partir del archivo D0.md adjunto genera requirements.md con:
1. Cinco historias de usuario como máximo, formato «Como [rol] quiero [acción] para [beneficio]».
2. Criterios de aceptación en formato EARS (WHEN ... THE SYSTEM SHALL ...) para cada historia.
3. Un criterio de aceptación por cada regla de negocio R1 a R4, citando el código de la regla.
Restricciones: no agregues funcionalidades fuera del alcance declarado en D0. No incluyas autenticación ni reportes. Responde solo con el contenido del archivo.
```

## P3 Diseño

**Dónde se utiliza:** Kiro, chat en modo spec.

```text
A partir de requirements.md genera design.md con:
1. Arquitectura en tres capas para PHP sin framework: presentación (archivos .php con HTML), lógica de negocio (una clase ServicioVenta en src/ sin HTML ni SQL) y acceso a datos (clase RepositorioMySQL en src/ usando PDO con sentencias preparadas).
2. Modelo de datos con las tres tablas producto, venta y detalle_venta, con tipos y claves.
3. Diagrama de secuencia en texto (Mermaid) del caso «registrar venta», indicando en qué capa se valida cada regla R1–R4.
Restricción: la clase ServicioVenta debe poder probarse con PHPUnit sin base de datos.
```

## P4 Script de base de datos

**Dónde se utiliza:** VS Code, chat del agente de código.

```text
Lee design.md. Genera el archivo sql/bodega.sql para MySQL 8 (XAMPP) con:
- CREATE DATABASE bodega; USE bodega;
- Las tres tablas con claves primarias, foráneas y tipos exactos del diseño.
- Datos de prueba: 6 productos con nombres de una bodega peruana (arroz, aceite, leche, azúcar, atún, gaseosa) con precios entre S/ 2,50 y S/ 12,00 y stock entre 0 y 50. Uno de los productos debe tener stock 0.
No generes procedimientos almacenados ni triggers. Explica en dos líneas qué hiciste.
```

## P5 Código de la aplicación

**Dónde se utiliza:** VS Code, chat del agente de código.

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

**Dónde se utiliza:** VS Code, chat del agente de código.

```text
Genera una lista de cotejo de 8 pruebas manuales para esta aplicación, una por fila, con columnas: N.°, acción exacta en pantalla, dato de entrada, resultado esperado según la regla R1–R4 que aplica. Incluye obligatoriamente: vender exactamente el stock disponible, vender uno más que el stock, subtotal de exactamente S/ 100,00, subtotal de S/ 100,01, cantidad 0 y cantidad -1. No ejecutes nada; solo la tabla.
```

## P7 Refactorización para testeabilidad

**Dónde se utiliza:** VS Code, chat del agente de código.

```text
Revisa src/ServicioVenta.php. Si algún cálculo o validación de las reglas R1–R4 está fuera de esa clase (en guardar_venta.php o en RepositorioMySQL.php), muévelo a ServicioVenta sin cambiar el comportamiento. ServicioVenta no debe tener echo, HTML, PDO ni variables de sesión. Al terminar, lista cada método con su firma y la regla que implementa, y confirma que la aplicación sigue funcionando igual.
```

## P8 TDD rojo

**Dónde se utiliza:** Editor de texto para el test y chat de VS Code para revisar únicamente su sintaxis.

```text
Revisa únicamente la sintaxis de tests/ServicioVentaTest.php. No implementes nada en src/. No modifiques la aserción ni el valor esperado. Dime si el archivo compila y si el nombre del método describe el comportamiento probado.
```

## P9 TDD verde

**Dónde se utiliza:** VS Code, chat del agente de código.

```text
Lee tests/ServicioVentaTest.php. Implementa en src/ServicioVenta.php el código MÍNIMO necesario para que ese test pase. No agregues métodos, validaciones ni casos que el test no exija. No modifiques el archivo de pruebas. Explica en una línea qué cambiaste.
```

## P10 Batería completa de pruebas unitarias

**Dónde se utiliza:** VS Code, chat del agente de código.

```text
Genera en tests/ServicioVentaTest.php una batería de pruebas PHPUnit para la clase ServicioVenta que cubra las reglas R1, R2 y R3 con la técnica de valores límite: para cada regla, el valor justo en el borde, uno por debajo y uno por encima. Mínimo 10 métodos de prueba. Cada método debe tener un nombre que describa el comportamiento en español (testRechazaCantidadCero, testAceptaVentaIgualAlStock...). Usa assertSame para enteros y assertEqualsWithDelta con 0.001 para decimales. No pruebes RepositorioMySQL ni ningún archivo con acceso a base de datos. Al final, lista qué casos NO quedaron cubiertos y por qué.
```

## P11 Análisis de mutantes sobrevivientes

**Dónde se utiliza:** VS Code, chat del agente de código, después del intercambio entre parejas.

```text
Contexto: otra pareja plantó 3 defectos en src/ServicioVenta.php y nuestra suite dejó pasar [N] de ellos. Aquí está el diff de los defectos: [pegar diff]. Para cada defecto que sobrevivió, explica por qué ningún test lo detectó y escribe el test mínimo que lo habría atrapado. No modifiques src/. No reescribas la suite completa; solo agrega los tests faltantes.
```

## P12 Preparación para despliegue

**Dónde se utiliza:** VS Code, chat del agente de código.

```text
Prepara la aplicación para desplegarla en un hosting PHP compartido (InfinityFree) con estas condiciones:
1. config/env.php debe leer primero variables de entorno (getenv) y, si no existen, usar los valores locales de XAMPP. Nunca debe subirse con credenciales reales: genera config/env.ejemplo.php y agrega config/env.php al .gitignore.
2. Genera sql/bodega_produccion.sql SIN la sentencia CREATE DATABASE (el hosting ya crea la base) y SIN USE.
3. Revisa que ninguna ruta sea absoluta de Windows (C:\xampp...).
4. Genera un README.md con los pasos exactos de despliegue en InfinityFree: crear cuenta, crear base MySQL, importar SQL en phpMyAdmin, subir archivos a htdocs por el administrador de archivos, editar env.php con los datos del panel.
Lista al final todo lo que cambiaste.
```
