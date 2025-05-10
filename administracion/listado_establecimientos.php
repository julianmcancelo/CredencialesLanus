<?php
require_once '../conexion.php';
session_start();
$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Administrador';

$mensaje = $_GET['mensaje'] ?? null;
$stmt = $pdo->query("SELECT * FROM establecimientos ORDER BY id DESC");
$establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Establecimientos</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-blue-800 text-white p-4 shadow">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
      <h1 class="text-lg font-bold">Panel de Administración - Establecimientos</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm">👤 <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="logout.php" class="text-sm underline hover:text-gray-200">Cerrar sesión</a>
      </div>
    </div>
  </header>

  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4 text-center text-blue-700">Listado de Establecimientos</h1>

    <?php if ($mensaje): ?>
      <div class="bg-blue-100 border border-blue-300 text-blue-700 px-4 py-2 mb-4 rounded">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
      <table class="min-w-full table-auto text-sm">
        <thead class="bg-gray-200 text-gray-700 uppercase">
          <tr>
            <th class="px-4 py-2">ID</th>
            <th class="px-4 py-2">Nombre</th>
            <th class="px-4 py-2">Domicilio</th>
            <th class="px-4 py-2">Localidad</th>
            <th class="px-4 py-2">Latitud</th>
            <th class="px-4 py-2">Longitud</th>
            <th class="px-4 py-2">Ver en Mapa</th>
            <th class="px-4 py-2">Editar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($establecimientos as $e): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-2"><?= $e['id'] ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($e['nombre']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($e['domicilio']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($e['localidad']) ?></td>
            <td class="px-4 py-2"><?= $e['latitud'] ?></td>
            <td class="px-4 py-2"><?= $e['longitud'] ?></td>
            <td class="px-4 py-2">
              <a href="https://www.google.com/maps?q=<?= $e['latitud'] ?>,<?= $e['longitud'] ?>" target="_blank" class="text-blue-600 hover:underline">📍 Ver</a>
            </td>
            <td class="px-4 py-2">
              <button onclick="abrirModalEditarEstablecimiento(
                <?= $e['id'] ?>, 
                '<?= htmlspecialchars(addslashes($e['nombre'])) ?>',
                '<?= htmlspecialchars(addslashes($e['domicilio'])) ?>',
                '<?= htmlspecialchars(addslashes($e['localidad'])) ?>',
                '<?= $e['latitud'] ?>', 
                '<?= $e['longitud'] ?>', 
                '<?= htmlspecialchars(addslashes($e['direccion'])) ?>'
              )" class="text-yellow-600 hover:underline">Editar</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="text-center mt-6">
      <a href="index.php" class="inline-block text-sm text-blue-700 hover:underline mr-4">← Volver al Panel</a>
      <a href="../index.php" class="inline-block text-sm text-blue-700 hover:underline">🏠 Ir al Inicio</a>
    </div>
  </div>

  <!-- Modal de edición -->
  <div id="modalEditarEstablecimiento" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
      <button onclick="cerrarModalEditarEstablecimiento()" class="absolute top-2 right-2 text-gray-600 hover:text-red-600 text-xl">&times;</button>
      <h2 class="text-lg font-bold text-blue-700 mb-4">Editar Establecimiento</h2>
      <form method="POST" action="guardar_edicion_establecimientos.php" class="space-y-4">
        <input type="hidden" name="id" id="establecimiento-id">

        <label class="block">
          <span class="text-sm font-medium">Nombre:</span>
          <input type="text" name="nombre" id="establecimiento-nombre" class="w-full mt-1 border rounded px-3 py-2" required>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Domicilio:</span>
          <input type="text" name="domicilio" id="establecimiento-domicilio" class="w-full mt-1 border rounded px-3 py-2" required>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Localidad:</span>
          <input type="text" name="localidad" id="establecimiento-localidad" class="w-full mt-1 border rounded px-3 py-2" required>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Latitud:</span>
          <input type="text" name="latitud" id="establecimiento-latitud" class="w-full mt-1 border rounded px-3 py-2" required>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Longitud:</span>
          <input type="text" name="longitud" id="establecimiento-longitud" class="w-full mt-1 border rounded px-3 py-2" required>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Dirección Completa:</span>
          <input type="text" name="direccion" id="establecimiento-direccion" class="w-full mt-1 border rounded px-3 py-2" required>
        </label>

        <div class="text-right">
          <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            ✅ Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function abrirModalEditarEstablecimiento(id, nombre, domicilio, localidad, latitud, longitud, direccion) {
      document.getElementById('establecimiento-id').value = id;
      document.getElementById('establecimiento-nombre').value = nombre;
      document.getElementById('establecimiento-domicilio').value = domicilio;
      document.getElementById('establecimiento-localidad').value = localidad;
      document.getElementById('establecimiento-latitud').value = latitud;
      document.getElementById('establecimiento-longitud').value = longitud;
      document.getElementById('establecimiento-direccion').value = direccion;
      document.getElementById('modalEditarEstablecimiento').classList.remove('hidden');
    }

    function cerrarModalEditarEstablecimiento() {
      document.getElementById('modalEditarEstablecimiento').classList.add('hidden');
    }
  </script>
</body>
</html>
