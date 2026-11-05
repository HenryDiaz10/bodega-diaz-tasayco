<?php
declare(strict_types=1);
// Ensayo local de E2: no representa un intercambio con otra pareja ni un ciclo TDD.
$raiz = dirname(__DIR__);
$carpeta = $raiz . '/tmp/mutantes-' . bin2hex(random_bytes(5));
mkdir($carpeta . '/src', 0777, true);
mkdir($carpeta . '/tests');
$fuente = file_get_contents($raiz . '/src/ServicioVenta.php');
copy(__DIR__ . '/ServicioVentaTest.php', $carpeta . '/tests/ServicioVentaTest.php');
$mutaciones = [
    'M1 stock exacto' => ['$cantidad <= $stock', '$cantidad < $stock'],
    'M2 descuento en 100' => ['$subtotal > 100 ?', '$subtotal >= 100 ?'],
    'M3 acepta cantidad cero' => ['$cantidad >= 1 &&', '$cantidad >= 0 &&'],
];
$salidaDir = $raiz . '/evidencias/mutantes';
if (!is_dir($salidaDir)) mkdir($salidaDir, 0777, true);
function ejecutar(array $comando): array {
    $proceso = proc_open($comando, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($proceso)) throw new RuntimeException('No se pudo ejecutar PHPUnit');
    fclose($pipes[0]);
    $salida = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($proceso), $salida];
}
$comando = [PHP_BINARY, $raiz . '/phpunit.phar', '--no-configuration', '--colors=never', '--do-not-cache-result', $carpeta . '/tests/ServicioVentaTest.php'];
$informe = "# Ensayo local de mutaciones\n\nPruebas reales contra copias aisladas. No se alteró src/ServicioVenta.php. No sustituye el intercambio con la pareja rival.\n\n| Variante | Resultado |\n|---|---|\n";
$detectados = 0;
try {
    file_put_contents($carpeta . '/src/ServicioVenta.php', $fuente);
    [$codigo, $salida] = ejecutar($comando);
    file_put_contents($salidaDir . '/base.txt', $salida);
    if ($codigo !== 0) throw new RuntimeException('La suite base debe estar verde');
    $informe .= "| Base sin mutar | VERDE |\n";
    foreach ($mutaciones as $nombre => [$buscar, $reemplazar]) {
        if (substr_count($fuente, $buscar) !== 1) throw new RuntimeException('Mutación ambigua: ' . $nombre);
        file_put_contents($carpeta . '/src/ServicioVenta.php', str_replace($buscar, $reemplazar, $fuente));
        [$sintaxis] = ejecutar([PHP_BINARY, '-l', $carpeta . '/src/ServicioVenta.php']);
        if ($sintaxis !== 0) throw new RuntimeException('Mutación no válida');
        [$codigo, $salida] = ejecutar($comando);
        $id = substr($nombre, 0, 2);
        file_put_contents($salidaDir . '/' . $id . '.txt', $salida);
        file_put_contents($salidaDir . '/' . $id . '.diff', "--- src/ServicioVenta.php\n+++ copia mutada\n@@\n-" . $buscar . "\n+" . $reemplazar . "\n");
        $muerto = in_array($codigo, [1, 2], true) && preg_match('/Failures: [1-9]/', $salida) === 1;
        $detectados += (int) $muerto;
        $informe .= '| ' . $nombre . ' | ' . ($muerto ? 'DETECTADO por la suite' : 'REVISAR') . " |\n";
    }
} finally {
    unlink($carpeta . '/src/ServicioVenta.php');
    unlink($carpeta . '/tests/ServicioVentaTest.php');
    rmdir($carpeta . '/src'); rmdir($carpeta . '/tests'); rmdir($carpeta);
}
$informe .= "\nResultado: $detectados/3 mutaciones detectadas. Logs íntegros en evidencias/mutantes/.\n";
file_put_contents($salidaDir . '/resultado.md', $informe);
echo $informe;
exit($detectados === 3 ? 0 : 1);
