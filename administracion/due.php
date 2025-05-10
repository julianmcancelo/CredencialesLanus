<?php
session_start();

// PROTECCION: Solo para rol 'dueño'
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    die('Acceso denegado.');
}

// Variable para archivo de mantenimiento
$mantenimientoArchivo = 'maintenance.lock';

if (isset($_POST['accion'])) {
    if ($_POST['accion'] === 'activar_mantenimiento') {
        file_put_contents($mantenimientoArchivo, 'Sistema en mantenimiento.');
    } elseif ($_POST['accion'] === 'desactivar_mantenimiento') {
        if (file_exists($mantenimientoArchivo)) unlink($mantenimientoArchivo);
    } elseif ($_POST['accion'] === 'backup_bd') {
        backupBaseDeDatos();
    }
}

// FUNCION: Backup de la base de datos
function backupBaseDeDatos() {
    $host = 'localhost';
    $usuario = 'transpo1_credenciales';
    $clave = 'feelthesky1';
    $basedatos = 'transpo1_credenciales';

    $fecha = date('Y-m-d_H-i-s');
    $nombre_backup = "backup_{$basedatos}_{$fecha}.sql";

    // Comando mysqldump
    $comando = "mysqldump --user={$usuario} --password={$clave} --host={$host} {$basedatos} > {$nombre_backup}";

    system($comando, $resultado);

    if ($resultado === 0 && file_exists($nombre_backup)) {
        // Forzar descarga
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($nombre_backup));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($nombre_backup));
        readfile($nombre_backup);
        unlink($nombre_backup); // Borrar backup después de descargar
        exit;
    } else {
        echo "❌ Error al generar el backup.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modo Dueño - Panel Exclusivo</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-6">
  <div class="bg-white shadow-lg rounded-xl p-8 max-w-md w-full">
    <h1 class="text-2xl font-bold text-center mb-6">Panel Modo Dueño 👑</h1>

    <form method="POST" class="space-y-4">
      <button name="accion" value="activar_mantenimiento" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">🛠️ Activar Mantenimiento</button>

      <button name="accion" value="desactivar_mantenimiento" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">🔄 Desactivar Mantenimiento</button>

      <button name="accion" value="backup_bd" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">📁 Descargar Backup BD</button>
    </form>

    <div class="text-center text-gray-500 text-sm mt-6">
      © <?php echo date('Y'); ?> Tu Sistema Exclusivo
    </div>
  </div>
</body>
</html>
