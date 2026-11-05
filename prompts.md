# Registro de uso de IA

Herramienta utilizada en esta conversación: Codex. Registro reconstruido a partir de mensajes visibles, no un historial de trabajo manual ni de Kiro. Las decisiones indicadas son resultados observables; la valoración personal del estudiante debe completarse por él.

| ID | Pedido registrado | Resultado y decisión observable |
|---|---|---|
| P1 | Documento base | Ya existía documents/d0.md al revisar el proyecto. No se verificó su autoría manual. D0.md en raíz organiza ese contenido y conserva literalmente R1–R4. |
| P2–P3 | Requisitos y diseño | Ya existían en .kiro/specs/bodega-ventas. No hay registro visible de los prompts originales de Kiro. Se actualizaron y copiaron a raíz al detectar contradicciones con la guía. |
| P4 | «Lee design.md. Genera O MODIFICA O MEJORA el archivo sql/bodega.sql…» | Se conservaron tipos y claves del diseño existente y seis productos. Se añadieron UTF-8 y reimportación sin sobrescribir inventario. |
| P5 | «Lee design.md y sql/bodega.sql. Genera la aplicación PHP 8 sin framework…» | Se implementaron clases estáticas, repositorio único, múltiples líneas, PDO, transacción y Bootstrap. |
| P6 | «Genera una lista de cotejo de 8 pruebas manuales… No ejecutes nada; solo la tabla» | Se entregó la tabla. Después el usuario pidió «REALIZA LAS PRUEBAS (LAS 8) Y VVERIDICA»: se ejecutaron por HTTP y base de datos, sin afirmar ejecución visual manual. |
| P7 | «Revisa src/ServicioVenta.php… muévelo a ServicioVenta sin cambiar el comportamiento» | Se centralizaron validaciones y cálculos. Se mantuvo R4 en el repositorio por requerir persistencia. |
| A1 | «Mejora todo pero ten en cuenta lo que no debes hacer…» | Mejoras de borrador, búsqueda, validación y documentación. Pedido amplio previo a la lectura de la guía; no se presenta como prompt objetivo del laboratorio. |
| A2 | «mejora el diseño de eso» y «agégale gráficos de barra u otros…» | Catálogo con paleta verde y crema y gráficos de inventario actual, sin librerías JS adicionales ni reportes históricos. |
| A3 | «lee los requeriments, design… mejora lo que tengas que mejorar» | Se completaron alta y edición de productos, errores por campo y pruebas. |
| S11 | «Revisa donde voy y haz lo que me falta, dejalo todo perfecto» | Se auditó la guía adjunta. Se separaron cambios técnicos de evidencias personales no realizadas; no se inventaron commits de TDD ni firmas. |
| S11-R2 | Pregunta de aclaración sobre la regla global o por línea | Respuesta literal del usuario: «Aplicar la guía: descuento por venta». Se cambió R2 global y se actualizaron servicio, vista y expectativas de pruebas. |
| S11-Git | Solicitud de URL del repositorio y hosting | Usuario proporcionó https://github.com/HenryDiaz10/bodega-diaz-tasayco.git. No se proporcionó URL pública de hosting. |

## Revisión individual pendiente

Cada integrante debe agregar su regla elegida, el test que escribió a mano, prompts realmente usados durante su ciclo TDD, cambios propios y por qué los aceptó. No existe en esta conversación un P8 o P9 escrito y ejecutado por el estudiante antes de la implementación; no se reconstruye esa evidencia retrospectivamente.
