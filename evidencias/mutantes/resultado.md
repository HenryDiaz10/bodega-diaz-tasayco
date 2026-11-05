# Ensayo local de mutaciones

Pruebas reales contra copias aisladas. No se alteró src/ServicioVenta.php. No sustituye el intercambio con la pareja rival.

| Variante | Resultado |
|---|---|
| Base sin mutar | VERDE |
| M1 stock exacto | DETECTADO por la suite |
| M2 descuento en 100 | DETECTADO por la suite |
| M3 acepta cantidad cero | DETECTADO por la suite |

Resultado: 3/3 mutaciones detectadas. Logs íntegros en evidencias/mutantes/.
