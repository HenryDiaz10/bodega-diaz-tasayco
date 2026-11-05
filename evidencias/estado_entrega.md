# Estado de entrega S11

## Completado técnicamente

- Aplicación local con catálogo, registro y edición de productos y venta de varias líneas.
- R1 permite igualdad de stock y rechaza exceso; R3 rechaza cero, negativos y decimales antes del cast.
- R2 global confirmado por el usuario: descuento del 5 % solo cuando la suma de subtotales supera 100,00.
- ServicioVenta puro, repositorio preparado con transacción, stock bloqueado y rollback probado.
- 23 pruebas unitarias PHPUnit y al menos diez métodos de valores límite; integración separada.
- Ensayo local de tres mutaciones de una línea detectadas por la suite. Logs íntegros en mutantes/.
- Configuración por entorno, ejemplo sin claves, .gitignore y SQL de producción sin CREATE DATABASE ni USE.
- Documentos de raíz y registro de prompts reconstruido con sus limitaciones explícitas.

## Pendiente de evidencia humana o externa

| Requisito de guía | Estado verificable | Próximo paso |
|---|---|---|
| E1 test escrito a mano antes de implementar | No demostrado | Elegir un comportamiento nuevo con la docente y realizar un ciclo real; no borrar código ni simular fechas para fabricar historia. |
| E1 commits rojo, verde, refactor por integrante | No existen en el estado recibido | Registrar solo trabajo que realmente se haga; usar el autor real. |
| Capturas de rojo y verde de cada integrante | No disponibles | Capturar el ciclo real; los logs de mutaciones no son E1. |
| E2 rama mutantes y revisión rival | main y mutantes publicados en el repositorio del equipo; intercambio pendiente | Intercambiar la rama con la pareja designada y registrar la prueba rival. |
| Suite propia sobre mutantes rivales | Pendiente | Obtener el código de la otra pareja y guardar el resultado auténtico. |
| E3 URL pública | No proporcionada | Crear hosting, configurar secretos fuera de Git y subir el paquete. |
| E3 acceso desde celular con datos | No comprobado | Registrar venta y stock antes/después desde el otro dispositivo. |
| Lista firmada de revisión cruzada | Pendiente | Completar revision_cruzada.md con la pareja revisora. |

## Diferencias conservadas del proyecto

El modelo existente usa nombre y cliente VARCHAR(100), precio_unitario DECIMAL(10,2) y subtotal/descuento por detalle. El cuadro resumido de la guía usa VARCHAR(80), precio DECIMAL(8,2), importes globales y detalle.importe. Se conserva el esquema vigente para no truncar ni perder datos; design.md declara sus tipos exactos y el subtotal/descuento global se reconstruye de los detalles. La adaptación exacta de nombres de columnas al cuadro de la guía requiere que la docente acepte esta equivalencia o autorice una migración específica. No se modificaron las ventas históricas al cambiar R2.

La ejecución fue local en PHP 8.2 y MariaDB 10.4 de XAMPP, no en un servidor MySQL 8 independiente. La UI visual y JavaScript no se inspeccionaron en navegador. Las ocho pruebas son HTTP y base de datos; no se describen como ejecución manual del estudiante.

Repositorio publicado: https://github.com/HenryDiaz10/bodega-diaz-tasayco. La rama main conserva el código correcto; mutantes introduce exactamente tres cambios intencionales en ServicioVenta. No desplegar mutantes.
