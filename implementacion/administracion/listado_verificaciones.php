<?php
require_once '../conexion.php';
session_start();
$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Administrador';

$mensaje = $_GET['mensaje'] ?? null;

$stmt = $pdo->query("SELECT t.*, 
  hg.nro_licencia, 
  GROUP_CONCAT(DISTINCT p.nombre SEPARATOR ' / ') AS titular_nombre, 
  GROUP_CONCAT(DISTINCT v.dominio SEPARATOR ' / ') AS dominio 
  FROM turnos t
  LEFT JOIN habilitaciones_generales hg ON hg.id = t.habilitacion_id
  LEFT JOIN habilitaciones_personas hp ON hp.habilitacion_id = t.habilitacion_id AND hp.rol = 'TITULAR'
  LEFT JOIN personas p ON p.id = hp.persona_id
  LEFT JOIN habilitaciones_vehiculos hv ON hv.habilitacion_id = t.habilitacion_id
  LEFT JOIN vehiculos v ON v.id = hv.vehiculo_id
  GROUP BY t.id
  ORDER BY t.fecha DESC, t.hora DESC");
$verificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listado de Verificaciones</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-purple-800 text-white p-4 shadow">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
      <h1 class="text-lg font-bold">Panel de Administración - Verificaciones</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm">👤 <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="logout.php" class="text-sm underline hover:text-gray-200">Cerrar sesión</a>
      </div>
    </div>
  </header>

  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4 text-center text-purple-700">Listado de Verificaciones Vehiculares</h1>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-2 mb-4 rounded">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
      <table class="min-w-full table-auto text-sm">
        <thead class="bg-gray-200 text-gray-700 uppercase">
          <tr>
            <th class="px-4 py-2">Fecha</th>
            <th class="px-4 py-2">Hora</th>
            <th class="px-4 py-2">Licencia</th>
            <th class="px-4 py-2">Titular</th>
            <th class="px-4 py-2">Dominio</th>
            <th class="px-4 py-2">Observaciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($verificaciones as $v): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-2"><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
            <td class="px-4 py-2"><?= date('H:i', strtotime($v['hora'])) ?> hs</td>
            <td class="px-4 py-2 text-blue-700 font-semibold"><?= htmlspecialchars($v['nro_licencia'] ?? '---') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['titular_nombre'] ?? '---') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['dominio'] ?? '---') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['observaciones'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-center mt-4">
      <a href="admin.php" class="text-sm text-purple-700 hover:underline">← Volver al Panel</a>
    </div>
  </div>
</body>
</html>
