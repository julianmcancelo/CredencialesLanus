<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'conexion.php';
session_start();

// Validar si hay CSV cargado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    $archivoTmp = $_FILES['archivo_csv']['tmp_name'];

    if (($handle = fopen($archivoTmp, 'r')) !== false) {
        $cabeceras = fgetcsv($handle, 1000, ","); // Leer la primera línea (encabezados)

        $datosRaw = [];
        while (($fila = fgetcsv($handle, 1000, ",")) !== false) {
            $filaAsociativa = array_combine($cabeceras, $fila);
            $datosRaw[] = $filaAsociativa;
        }
        fclose($handle);

        // Organizar registros únicos, priorizando quien tenga email y teléfono
        $datosUnicos = [];

        foreach ($datosRaw as $fila) {
            $dni = preg_replace('/[^0-9]/', '', $fila['DNI Titular'] ?? '');

            if (!$dni) continue; // Saltar si no tiene DNI válido

            $email = trim($fila['Email'] ?? '');
            $telefono = preg_replace('/[^0-9]/', '', $fila['Telefono'] ?? '');

            if (!isset($datosUnicos[$dni])) {
                $datosUnicos[$dni] = $fila;
            } else {
                // Ya existe este DNI: decidir si lo reemplazamos
                $yaGuardado = $datosUnicos[$dni];
                $emailGuardado = trim($yaGuardado['Email'] ?? '');
                $telefonoGuardado = preg_replace('/[^0-9]/', '', $yaGuardado['Telefono'] ?? '');

                $prioridadActual = (!empty($email) ? 1 : 0) + (!empty($telefono) ? 1 : 0);
                $prioridadGuardado = (!empty($emailGuardado) ? 1 : 0) + (!empty($telefonoGuardado) ? 1 : 0);

                if ($prioridadActual > $prioridadGuardado) {
                    $datosUnicos[$dni] = $fila; // Reemplazar por el que tenga más info
                }
            }
        }

        $datos = array_values($datosUnicos); // Reindexar el array
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Importar Personas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-6 min-h-screen">

<div class="max-w-6xl mx-auto bg-white rounded-xl shadow p-6 space-y-8">

  <h1 class="text-2xl font-bold text-[#891628]">🗂️ Importar Personas desde CSV</h1>

  <?php if (!isset($datos)): ?>
  <form method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="file" name="archivo_csv" accept=".csv" required class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md p-2">
    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
      📂 Subir y Previsualizar
    </button>
  </form>

  <?php else: ?>

  <form method="POST" action="procesar_importacion.php">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-100 uppercase">
          <tr>
            <th class="px-4 py-2">Nombre</th>
            <th class="px-4 py-2">DNI</th>
            <th class="px-4 py-2">Domicilio</th>
            <th class="px-4 py-2">Teléfono</th>
            <th class="px-4 py-2">Email</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($datos as $index => $persona): ?>
          <tr class="border-b">
            <td class="px-4 py-2"><?= htmlspecialchars($persona['Nombre, Apellido Titular'] ?? '') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($persona['DNI Titular'] ?? '') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($persona['Domicilio'] ?? '') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($persona['CONTACTO'] ?? '') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($persona['Email'] ?? '') ?></td>
          </tr>

          <input type="hidden" name="personas[<?= $index ?>][nombre]" value="<?= htmlspecialchars($persona['Nombre, Apellido Titular'] ?? '') ?>">
          <input type="hidden" name="personas[<?= $index ?>][dni]" value="<?= htmlspecialchars($persona['DNI Titular'] ?? '') ?>">
          <input type="hidden" name="personas[<?= $index ?>][domicilio]" value="<?= htmlspecialchars($persona['Domicilio'] ?? '') ?>">
          <input type="hidden" name="personas[<?= $index ?>][telefono]" value="<?= htmlspecialchars($persona['Telefono'] ?? '') ?>">
          <input type="hidden" name="personas[<?= $index ?>][email]" value="<?= htmlspecialchars($persona['Email'] ?? '') ?>">

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="flex flex-wrap gap-4 mt-6">
      <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-semibold">
        ✅ Importar Todos
      </button>
      <a href="importar_personas.php" class="bg-gray-500 hover:bg-gray-700 text-white px-6 py-2 rounded font-semibold">
        ❌ Cancelar
      </a>
    </div>
  </form>

  <?php endif; ?>
</div>

</body>
</html>
