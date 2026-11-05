# Prompts y pasos de la guía S11

Registro limitado a las actividades P1–P12 de la guía de laboratorio 11. Los enunciados siguientes resumen los prompts de la guía; no son una transcripción de sesiones anteriores. Los pasos describen el trabajo comprobable en los archivos y distinguen las actividades pendientes.

## P1 Documento base

**Actividad de la guía:** redactar D0.md con problema, usuarios, alcance, reglas R1–R4 y restricciones técnicas. La guía reserva esta redacción al estudiante.

**Pasos comprobados:** se revisó documents/d0.md, se organizó D0.md en la raíz y se conservaron literalmente las cuatro reglas de la guía.

**Resultado:** documento disponible. La autoría manual inicial no está acreditada por este registro.

## P2 Requisitos

**Resumen del prompt:** actuar como analista de sistemas y generar requirements.md a partir de D0.md, con un máximo de cinco historias, criterios EARS y referencias a R1–R4, sin autenticación ni reportes.

**Pasos comprobados:** se revisaron los requisitos existentes, se mantuvieron cinco historias y se alineó R2 con el descuento sobre toda la venta. Se sincronizaron los archivos de raíz y .kiro/specs/bodega-ventas/.

**Resultado:** requirements.md actualizado. No se dispone del registro de la sesión original de elaboración de requisitos.

## P3 Diseño

**Resumen del prompt:** generar design.md con arquitectura de tres capas, ServicioVenta sin HTML ni SQL, RepositorioMySQL con PDO, las tres tablas y un diagrama Mermaid de registrar venta.

**Pasos comprobados:** se documentaron presentación, negocio y datos; se describió el esquema efectivo; se añadió la secuencia con validación, bloqueo, commit y rollback. Se conservaron métodos estáticos y un repositorio único.

**Resultado:** design.md actualizado y sincronizado. Las diferencias del esquema respecto al cuadro resumido de la guía están declaradas en el diseño.

## P4 Script de base de datos

**Resumen del prompt:** leer design.md y generar sql/bodega.sql para MySQL con base bodega, tres tablas, claves y seis productos peruanos con los precios y existencias indicados, sin procedimientos ni triggers.

**Pasos comprobados:** se revisaron tipos y claves; se mantuvieron los seis productos y uno sin stock; se configuró utf8mb4 y se verificó que reimportar las semillas no sobrescriba precio ni stock existentes.

**Resultado:** sql/bodega.sql y prueba tests/semillas.php disponibles. Ejecución verificada en MariaDB de XAMPP.

## P5 Aplicación PHP

**Resumen del prompt:** implementar la estructura config/, src/, index.php, venta.php y guardar_venta.php en PHP 8 sin framework, con métodos estáticos de negocio, PDO, varias líneas de venta y Bootstrap desde CDN.

**Pasos comprobados:** se separaron las capas; se implementó la conexión configurable; se incorporaron formulario, validaciones, cálculo global, persistencia preparada y actualización atómica del stock.

**Resultado:** aplicación local operativa. Los datos enviados por el formulario se validan en el servidor y los precios se obtienen de la base bajo bloqueo.

## P6 Lista de cotejo manual

**Resumen del prompt:** producir una tabla de ocho pruebas con acción en pantalla, entrada y resultado esperado, incluyendo stock exacto, exceso de una unidad, subtotales 100,00 y 100,01 y cantidades 0 y -1; sin ejecutarlas al generar la tabla.

**Pasos comprobados:** se preparó la lista en evidencias/lista_manual.md. Por separado se ejecutaron ocho casos HTTP y se comprobaron sus efectos en la base, registrados en tests/resultados_8.md.

**Resultado:** ocho casos automatizados aprobados. La ejecución visual manual y su número de fallos siguen pendientes de registro.

## P7 Refactorización

**Resumen del prompt:** mover cálculos y validaciones a ServicioVenta sin cambiar el comportamiento, sin echo, HTML, PDO ni sesiones; listar firmas y reglas.

**Pasos comprobados:** se centralizaron normalización, validación de cantidades, stock acumulado y cálculos. Se mantuvieron consultas y transacción en RepositorioMySQL. Se ejecutaron pruebas de regresión.

**Resultado:** ServicioVenta es comprobable sin base de datos; las responsabilidades están descritas en design.md.

## P8 TDD rojo

**Resumen del prompt:** revisar únicamente la sintaxis de un test escrito a mano antes de la implementación, sin modificar su aserción ni implementar src/.

**Estado:** pendiente de evidencia individual. No hay registro de un test manual anterior a su implementación ni de una ejecución roja de ese ciclo. Los fallos de mutaciones no se contabilizan como esta actividad.

## P9 TDD verde y refactor

**Resumen del prompt:** implementar lo mínimo para pasar el test anterior sin modificarlo; después refactorizar manteniendo pruebas y comportamiento.

**Estado:** pendiente como ciclo individual vinculado a P8. La suite actual está verde, pero ese resultado no acredita por sí solo la secuencia rojo → verde → refactor exigida en E1.

## P10 Batería de valores límite

**Resumen del prompt:** generar al menos diez métodos PHPUnit para R1–R3, probando debajo, en y encima del borde, con nombres descriptivos, valores esperados concretos y sin base de datos; indicar casos no cubiertos.

**Pasos comprobados:** se amplió tests/ServicioVentaTest.php con casos de stock, cantidades y descuento; se configuró phpunit.xml y se ejecutó la suite también desde una copia limpia del código.

**Resultado:** 23 pruebas y 1.127 aserciones aprobadas. R4 se verifica por integración; navegador, concurrencia real y hosting no forman parte de la suite unitaria.

## P11 Mutantes sobrevivientes

**Resumen del prompt:** analizar el diff de los tres defectos de la pareja rival, explicar por qué sobrevivieron y agregar únicamente los tests que faltan, sin modificar src/.

**Pasos comprobados de preparación:** se ensayaron tres mutaciones propias en copias aisladas y la suite detectó las tres. Se publicó la rama mutantes con exactamente tres cambios de una línea y sintaxis válida.

**Estado:** el intercambio rival y el análisis P11 siguen pendientes. Los resultados locales están en evidencias/mutantes/ y no representan pruebas contra otra pareja.

## P12 Preparación del despliegue

**Resumen del prompt:** configurar getenv con valores locales de respaldo, crear env.ejemplo.php, ignorar env.php, generar SQL sin CREATE DATABASE ni USE y documentar el despliegue en InfinityFree con rutas relativas.

**Pasos comprobados:** se actualizaron los archivos de configuración y .gitignore; se preparó sql/bodega_produccion.sql; se documentaron los pasos del hosting y se generó un paquete que excluye secretos, pruebas y PHPUnit. Se publicaron main y mutantes en GitHub.

**Resultado:** preparación técnica terminada. Quedan pendientes la URL pública, la venta desde celular con datos móviles y la revisión cruzada firmada.
