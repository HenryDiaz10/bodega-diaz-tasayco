# Desarrollo y verificación de Bodega Doña Rosa

Este documento describe el trabajo técnico que puede comprobarse en el proyecto. Los resultados de pruebas se distinguen de las evidencias individuales y las actividades externas pendientes.

## Pasos realizados

1. **Revisión funcional.** Se revisaron D0, requisitos y diseño para delimitar catálogo, productos, ventas y reglas R1–R4. Al contrastar la guía S11 se unificó el criterio del descuento sobre el subtotal global.
2. **Base de datos.** Se prepararon las tres tablas InnoDB con claves primarias y foráneas, precios decimales, nombres únicos y seis productos de prueba. Se mantuvo el esquema existente para conservar los datos.
3. **Separación de capas.** Se organizaron los archivos de presentación, ServicioVenta y RepositorioMySQL. Las validaciones y los cálculos quedaron en el servicio; las consultas preparadas y la transacción, en el repositorio.
4. **Venta de varias líneas.** Se incorporaron cliente, selección de productos, cantidades, vista previa de importes y recuperación del pedido cuando se rechaza la confirmación.
5. **Persistencia consistente.** Se bloquearon los productos por ID y se guardaron venta, detalles y descuento de stock en una transacción. Se comprobó la reversión ante un fallo deliberado.
6. **Mantenimiento de productos.** Se añadieron alta y edición, errores junto a cada campo, control de duplicados y mensajes de confirmación.
7. **Presentación del inventario.** Se mejoró el catálogo con búsqueda, filtros y gráficos del stock actual. La revisión visual completa en navegador continúa pendiente.
8. **Pruebas de negocio.** Se configuró PHPUnit y se ampliaron los casos de valores límite. La suite registrada contiene 23 pruebas y 1.127 aserciones. Esto acredita la ejecución actual, no un ciclo individual de TDD anterior a la implementación.
9. **Pruebas de integración.** Se ejecutaron ocho casos HTTP con comprobación de importes y stock, pruebas de productos y pruebas de commit y rollback. Los datos temporales se retiraron sin modificar los registros originales.
10. **Ensayo de mutaciones.** Se comprobaron por separado tres cambios de una línea sobre copias aisladas. La suite detectó los tres. Después se preparó la rama mutantes para el intercambio; no se afirma haber probado código de otra pareja.
11. **Preparación de despliegue.** Se añadió configuración por entorno, ejemplo sin secretos, exclusión de env.php, SQL de producción y un generador de paquete para hosting.
12. **Publicación del repositorio.** Se publicaron main y mutantes, conservando el código correcto en main. Los commits registran esta entrega; no representan commits históricos de TDD.

## Evidencias disponibles

| Evidencia | Ubicación |
|---|---|
| Pruebas unitarias | tests/ServicioVentaTest.php y phpunit.xml |
| Ocho casos HTTP | tests/resultados_8.md |
| Transacción y reversión | tests/transaccion.php |
| Registro y edición de productos | tests/productos_http.php |
| Resultados y diferencias de mutaciones | evidencias/mutantes/ |
| Commits publicados | git log y repositorio GitHub |
| Actividades pendientes | evidencias/estado_entrega.md |

## Pendiente de completar

Evidencia individual del ciclo TDD, ejecución manual visual, intercambio con la pareja rival, hosting público, prueba desde celular y revisión cruzada firmada. Estas actividades deben documentarse después de realizarlas.
