<?php
require_once 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("ID de vehículo inválido.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sql = "UPDATE vehiculos SET dominio=?, marca=?, modelo=?, motor=?, asientos=?, año=?, inscripcion_inicial=?, tipo=? WHERE id=?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $_POST['dominio'],
    $_POST['marca'],
    $_POST['modelo'],
    $_POST['motor'],
    $_POST['asientos'],
    $_POST['anio'],
    $_POST['inscripcion_inicial'],
    $_POST['tipo'],
    $id
  ]);
  $mensaje = "✅ Vehículo actualizado correctamente.";
}

$stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
$stmt->execute([$id]);
$vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$vehiculo) die("Vehículo no encontrado.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Vehículo</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold text-blue-700 mb-4 text-center">Editar Vehículo</h1>
    <?php if (isset($mensaje)): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm"> <?= $mensaje ?> </div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
      <input name="dominio" value="<?= htmlspecialchars($vehiculo['dominio']) ?>" class="w-full border px-3 py-2 rounded" required>
      <input name="marca" value="<?= htmlspecialchars($vehiculo['marca']) ?>" class="w-full border px-3 py-2 rounded" required>
      <input name="modelo" value="<?= htmlspecialchars($vehiculo['modelo']) ?>" class="w-full border px-3 py-2 rounded" required>
      <input name="motor" value="<?= htmlspecialchars($vehiculo['motor']) ?>" class="w-full border px-3 py-2 rounded">
      <input name="asientos" type="number" value="<?= $vehiculo['asientos'] ?>" class="w-full border px-3 py-2 rounded">
      <input name="anio" type="number" value="<?= $vehiculo['año'] ?>" class="w-full border px-3 py-2 rounded">
      <input name="inscripcion_inicial" value="<?= htmlspecialchars($vehiculo['inscripcion_inicial']) ?>" class="w-full border px-3 py-2 rounded">
      <input name="tipo" value="<?= htmlspecialchars($vehiculo['tipo']) ?>" class="w-full border px-3 py-2 rounded">
      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Actualizar Vehículo</button>
    </form>
    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-gray-600 hover:underline">← Volver al listado</a>
    </div>
  </div>
</body>
</html>
