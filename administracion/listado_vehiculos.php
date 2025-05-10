<?php
require_once '../conexion.php';
session_start();
$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Administrador';

$mensaje = $_GET['mensaje'] ?? null;

$stmt = $pdo->query("SELECT * FROM vehiculos ORDER BY id DESC");
$vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehículos</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-green-800 text-white p-4 shadow">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
      <h1 class="text-lg font-bold">Panel de Administración - Vehículos</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm">👤 <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="logout.php" class="text-sm underline hover:text-gray-200">Cerrar sesión</a>
      </div>
    </div>
  </header>

  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4 text-center text-green-700">Listado de Vehículos</h1>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-2 mb-4 rounded">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
      <table class="min-w-full table-auto text-sm">
        <thead class="bg-gray-200 text-gray-700 uppercase">
          <tr>
            <th class="px-4 py-2">ID</th>
            <th class="px-4 py-2">Dominio</th>
            <th class="px-4 py-2">Marca</th>
            <th class="px-4 py-2">Modelo</th>
            <th class="px-4 py-2">Motor</th>
            <th class="px-4 py-2">Asientos</th>
            <th class="px-4 py-2">Inscripción Inicial</th>
            <th class="px-4 py-2">Editar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vehiculos as $v): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-2"><?= $v['id'] ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['dominio']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['marca']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['modelo']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['motor']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['asientos']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['inscripcion_inicial']) ?></td>
            <td class="px-4 py-2">
              <button onclick="abrirModalEditarVehiculo(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['dominio'])) ?>', '<?= htmlspecialchars(addslashes($v['marca'])) ?>', '<?= htmlspecialchars(addslashes($v['modelo'])) ?>', '<?= htmlspecialchars(addslashes($v['motor'])) ?>', '<?= $v['asientos'] ?>', '<?= $v['inscripcion_inicial'] ?>')" class="text-yellow-600 hover:underline">Editar</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal de edición de vehículo -->
  <div id="modalEditarVehiculo" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
      <button onclick="cerrarModalEditarVehiculo()" class="absolute top-2 right-2 text-gray-600 hover:text-red-600 text-xl">&times;</button>
      <h2 class="text-lg font-bold text-green-700 mb-4">Editar Vehículo</h2>
      <form action="guardar_edicion_vehiculo.php" method="POST" class="space-y-4">
        <input type="hidden" name="id" id="vehiculo-id">
        <div>
          <label class="block text-sm font-medium">Dominio</label>
          <input type="text" name="dominio" id="vehiculo-dominio" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Marca</label>
          <input type="text" name="marca" id="vehiculo-marca" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Modelo</label>
          <input type="text" name="modelo" id="vehiculo-modelo" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Motor</label>
          <input type="text" name="motor" id="vehiculo-motor" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Asientos</label>
          <input type="number" name="asientos" id="vehiculo-asientos" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Inscripción Inicial</label>
          <input type="date" name="inscripcion_inicial" id="vehiculo-inscripcion" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div class="text-right">
          <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function abrirModalEditarVehiculo(id, dominio, marca, modelo, motor, asientos, inscripcion) {
      document.getElementById('vehiculo-id').value = id;
      document.getElementById('vehiculo-dominio').value = dominio;
      document.getElementById('vehiculo-marca').value = marca;
      document.getElementById('vehiculo-modelo').value = modelo;
      document.getElementById('vehiculo-motor').value = motor;
      document.getElementById('vehiculo-asientos').value = asientos;
      document.getElementById('vehiculo-inscripcion').value = inscripcion;
      document.getElementById('modalEditarVehiculo').classList.remove('hidden');
    }

    function cerrarModalEditarVehiculo() {
      document.getElementById('modalEditarVehiculo').classList.add('hidden');
    }
  </script>
</body>
</html>