<?php
declare(strict_types=1);
$raiz = dirname(__DIR__);
if (!is_dir($raiz . '/dist')) mkdir($raiz . '/dist', 0777, true);
$ruta = $raiz . '/dist/bodega-hosting.zip';
if (is_file($ruta)) unlink($ruta);
$zip = new PharData($ruta, 0, null, Phar::ZIP);
$archivos = ['index.php', 'venta.php', 'guardar_venta.php', 'producto_nuevo.php', 'producto_editar.php',
    'config/conexion.php', 'config/env.ejemplo.php'];
foreach (['assets', 'src', 'templates'] as $carpeta) {
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz . '/' . $carpeta, FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $archivo) if ($archivo->isFile()) {
        $archivos[] = str_replace('\\', '/', substr($archivo->getPathname(), strlen($raiz) + 1));
    }
}
foreach ($archivos as $archivo) $zip->addFile($raiz . '/' . $archivo, $archivo);
unset($zip);
echo "Paquete generado en dist/bodega-hosting.zip; importar SQL y configurar env.php por separado.\n";
