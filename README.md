# Bodega Doña Rosa

Aplicación PHP 8 con PDO y tablas InnoDB para XAMPP o hosting PHP compartido. Repositorio del equipo: <https://github.com/HenryDiaz10/bodega-diaz-tasayco>. URL pública: pendiente de creación del hosting y prueba desde otro dispositivo.

Para instalar localmente, copie `config/env.ejemplo.php` como `config/env.php`, inicie Apache y MySQL, importe `sql/bodega.sql` en phpMyAdmin y abra <http://localhost/bodega/>. La conexión lee primero DB_HOST, DB_USER, DB_PASSWORD y DB_NAME; sin esas variables usa localhost, root, sin clave y bodega. Nunca confirme en Git config/env.php con secretos.

Si la base se creó antes de agregar el cliente a las ventas, ejecute **una sola vez** `sql/migrar_cliente.sql`. Reimportar el esquema no migra tablas existentes. Los productos de prueba se insertan sin reemplazar el precio ni el stock de los productos que ya existen.

## Funcionamiento

- El catálogo muestra precios y stock y permite buscar por nombre, con o sin tildes.
- El formulario admite varias líneas y muestra subtotal, descuento y neto por línea. Si una confirmación falla, «Corregir venta» recupera el pedido con el stock actualizado.
- R1 permite vender el stock exacto y comprueba el consumo acumulado de productos repetidos.
- R2 aplica 5 % sobre el subtotal de **toda la venta**, solo si supera S/ 100,00, con redondeo mitad hacia arriba. Este criterio fue confirmado por el usuario al revisar la guía S11. Dos líneas de S/ 60,00 generan descuento S/ 6,00 y total S/ 114,00. Los detalles muestran la parte proporcional del descuento global para conservar el esquema existente; las ventas históricas no se modifican.
- R3 acepta cantidades enteras de 1 a 9999; el servidor rechaza decimales antes de convertirlos.
- R4 bloquea los productos y guarda venta, detalles y descuento de stock dentro de una transacción. Un fallo revierte todo.

`ServicioVenta` contiene las reglas y cálculos, sin HTML, salida, sesiones, SQL ni acceso a base de datos. `RepositorioMySQL` obtiene los datos y ejecuta la transacción con sentencias preparadas. Los archivos de presentación gestionan HTTP, sesión, CSRF y HTML. La vista previa JavaScript orienta al cajero; el servidor vuelve a validar y calcula con los precios vigentes.

Se mantiene PHP sin framework, Bootstrap por CDN, sin procedimientos almacenados ni triggers. No incluye usuarios, contraseñas, reportes ni facturación electrónica. Los gráficos muestran únicamente el inventario actual.

Desde el catálogo, «Registrar producto» permite crear productos y «Editar» modificar nombre, precio y stock. El servicio devuelve todos los errores de campo; la restricción UNIQUE protege los nombres duplicados. Se acepta coma o punto decimal, sin separadores de miles. Tras guardar se redirige al catálogo con un mensaje de éxito, evitando repetir la escritura al actualizar la página.

Se conservan las decisiones solicitadas en la conversación: métodos estáticos en ServicioVenta y repositorio único RepositorioMySQL, en lugar de los repositorios separados y el constructor por interfaces del diseño inicial. Las reglas de negocio permanecen en el servicio; sesiones y HTML en presentación; SQL y transacciones en el repositorio.

## Verificación

Desde la raíz, con XAMPP en `C:/xampp`:

```powershell
& C:/xampp/php/php.exe phpunit.phar
& C:/xampp/php/php.exe tests/reglas.php
& C:/xampp/php/php.exe tests/transaccion.php
& C:/xampp/php/php.exe tests/semillas.php
& C:/xampp/php/php.exe tests/ocho_http.php
& C:/xampp/php/php.exe tests/productos_http.php
& C:/xampp/php/php.exe tests/mutantes.php
```

Las pruebas de transacción usan tablas temporales y provocan un fallo deliberado para verificar rollback; ese error se escribe en el registro de PHP. Las ocho pruebas HTTP requieren Apache y la base importada: crean sus propios productos y ventas, los limpian y verifican que los registros originales no cambien. Pueden avanzar los contadores AUTO_INCREMENT. El informe se guarda en `tests/resultados_8.md`.

PHPUnit ejecuta 23 pruebas unitarias, con casos explícitos justo por debajo, en y por encima de los bordes de R1–R3, sin conexión a base de datos ni bibliotecas adicionales. Las pruebas HTTP de productos verifican registro y edición, errores por campo, duplicados, mensajes de éxito, CSRF e inventario original intacto. `tests/mutantes.php` ensaya tres mutaciones de una línea en copias aisladas y guarda sus logs reales en `evidencias/mutantes/`; no sustituye el intercambio entre parejas.

La ejecución local se verificó en MariaDB 10.4 incluido en XAMPP; no equivale a una ejecución en MySQL 8. Las pruebas HTTP no verifican la interacción visual ni JavaScript en un navegador.

## Despliegue en InfinityFree

1. Cree su cuenta en <https://www.infinityfree.com/> con el correo que corresponda al equipo. Cree una cuenta de hosting y elija un subdominio. Espere su activación.
2. En el panel, abra **MySQL Databases** y cree una base. Guarde en privado el host, nombre de base, usuario y contraseña que indica el panel; el host remoto no es localhost.
3. Entre a phpMyAdmin desde el panel, seleccione la base y use **Importar** con `sql/bodega_produccion.sql`. Ese archivo no contiene CREATE DATABASE ni USE. No importe sql/bodega.sql en el hosting.
4. Suba a **htdocs** los archivos PHP de la raíz y las carpetas assets/, src/, templates/ y config/. Puede generar el paquete listo para subir ejecutando `php scripts/preparar_entrega.php` (o usando la ruta completa a php.exe). El ZIP en dist/ contiene solo esos archivos y excluye secretos, pruebas, SQL, PHPUnit, .git y evidencias.
5. En el servidor copie `config/env.ejemplo.php` a `config/env.php`. Si el panel permite variables de entorno, configure DB_HOST, DB_USER, DB_PASSWORD y DB_NAME. En caso contrario, cambie únicamente los valores de respaldo del archivo env.php en el servidor por los cuatro datos reales. No ponga claves en este README ni en GitHub.
6. Abra la URL del subdominio. Compruebe catálogo, formulario y una venta válida. Compruebe el stock antes y después. Haga la prueba desde un celular con datos móviles y registre la URL y el resultado en evidencias/revision_cruzada.md.
7. Antes de publicar código, use `git check-ignore config/env.php` y revise `git status --short`. Si un secreto llegó a publicarse, cámbielo en el hosting y retire el archivo del historial: añadirlo a .gitignore no elimina una filtración previa.

Los pasos de importación y subida siguen la [guía oficial de migración de InfinityFree](https://forum.infinityfree.com/t/how-to-migrate-your-website-to-infinityfree/49315/1). El paquete usa rutas relativas; las rutas Windows de los ejemplos de pruebas locales no se incluyen en el sitio publicado.

## Cobertura y límites de la entrega

Consulte `evidencias/estado_entrega.md` para distinguir verificaciones realizadas de actividades pendientes. No hay evidencia histórica de TDD rojo antes de verde escrita a mano: los tests generados por IA no se presentan como E1. No se ha realizado un intercambio con una pareja rival ni una revisión firmada. No se afirma tener URL pública hasta probarla.

R4 se comprueba con integración porque requiere base de datos; las pruebas unitarias cubren negocio sin PDO. No están cubiertos por la suite unitaria el navegador, estilos, dos compradores simultáneos, red real del hosting ni funcionamiento desde celular. Hay bloqueos y transacción, pero eso no equivale a una prueba de concurrencia. El desafío n8n de semana 12 es una actividad posterior, no se da por completado en esta entrega S11.
