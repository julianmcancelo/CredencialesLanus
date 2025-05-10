<?php
require_once '../conexion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensaje = null;
$archivoSubido = '';

// SUBIR ARCHIVO Y GUARDAR EN TEMPORAL
if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] == 0) {
    $nombreArchivo = 'verificaciones_temp_' . time() . '.csv';
    $rutaTemporal = __DIR__ . '/' . $nombreArchivo;

    if (move_uploaded_file($_FILES['archivo_csv']['tmp_name'], $rutaTemporal)) {
        $archivoSubido = $nombreArchivo;
    } else {
        $mensaje = "❌ Error al mover el archivo.";
    }
}

// CONFIRMAR IMPORTACIÓN
if (isset($_POST['confirmar']) && isset($_POST['archivo'])) {
    $archivo = __DIR__ . '/' . basename($_POST['archivo']);
    if (file_exists($archivo)) {
        if (($handle = fopen($archivo, "r")) !== false) {
            fgetcsv($handle, 1000, ","); // Saltar encabezado
            $importados = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (trim($data[0]) !== 'Remis') {
                    continue;
                }

                $nro_licencia = isset($data[1]) ? trim($data[1]) : '';
                $fecha_raw = trim($data[2] ?? '');
                $hora_raw = trim($data[3] ?? '');
                $nombre_titular = trim($data[4] ?? '') ?: 'N/A';
                $dominio = trim($data[9] ?? '') ?: 'N/A';
                $resultado = trim($data[16] ?? '') ?: 'PENDIENTE';
                $expediente = trim($data[17] ?? '') ?: 'N/A';

                $fecha = DateTime::createFromFormat('d/m/Y', $fecha_raw);
                $hora = DateTime::createFromFormat('H:i', $hora_raw);

                $fechaSQL = $fecha ? $fecha->format('Y-m-d') : null;
                $horaSQL = $hora ? $hora->format('H:i:s') : null;

                if ($nro_licencia && $fechaSQL && $horaSQL) {
                    $stmt = $pdo->prepare("
                        INSERT INTO verificaciones_historial 
                        (nro_licencia, fecha, hora, nombre_titular, dominio, resultado, expediente)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$nro_licencia, $fechaSQL, $horaSQL, $nombre_titular, $dominio, $resultado, $expediente]);
                    $importados++;
                }
            }
            fclose($handle);
            unlink($archivo); // Borrar el archivo temporal
            $mensaje = "✅ Se importaron correctamente <strong>$importados verificaciones</strong>.";
        } else {
            $mensaje = "❌ Error al leer el archivo.";
        }
    } else {
        $mensaje = "❌ Archivo no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Importar Verificaciones Vehiculares</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-md bg-white p-6 rounded-xl shadow-lg">
    <h1 class="text-2xl font-bold text-[#891628] mb-4 text-center">📤 Importar Verificaciones Vehiculares</h1>

    <?php if ($mensaje): ?>
      <div class="mb-4 text-center font-medium <?= strpos($mensaje, '✅') !== false ? 'text-green-600' : 'text-red-600' ?>">
        <?= $mensaje ?>
      </div>
    <?php endif; ?>

    <?php if ($archivoSubido): ?>
      <form method="POST" class="text-center">
        <input type="hidden" name="archivo" value="<?= htmlspecialchars($archivoSubido) ?>">
        <input type="hidden" name="confirmar" value="1">
        <p class="text-gray-700 mb-4">Se cargó el archivo correctamente.<br>¿Deseás confirmar la importación?</p>
        <button type="submit" class="bg-[#891628] hover:bg-red-800 text-white px-6 py-2 rounded font-semibold">
          ✅ Confirmar Importación
        </button>
      </form>
    <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Archivo CSV:</label>
          <input type="file" name="archivo_csv" accept=".csv" required class="w-full border px-3 py-2 rounded">
        </div>
        <div class="text-right">
          <button type="submit" class="bg-[#891628] hover:bg-red-800 text-white px-6 py-2 rounded font-semibold">
            Subir Archivo
          </button>
        </div>
      </form>
    <?php endif; ?>

    <div class="text-center mt-4">
      <a href="../index.php" class="text-blue-700 hover:underline text-sm">← Volver al Panel</a>
    </div>
  </div>
</body>
</html>
