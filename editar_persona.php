<?php
require_once 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("ID inválido.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sql = "UPDATE personas SET nombre=?, dni=?, cuit=?, telefono=?, email=?, foto_url=? WHERE id=?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $_POST['nombre'],
    $_POST['dni'],
    $_POST['cuit'],
    $_POST['telefono'],
    $_POST['email'],
    $_POST['foto_url'],
    $id
  ]);
  $mensaje = "✅ Datos actualizados correctamente.";
}

$stmt = $pdo->prepare("SELECT * FROM personas WHERE id = ?");
$stmt->execute([$id]);
$persona = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$persona) die("Persona no encontrada.");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Persona</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold text-blue-700 mb-4 text-center">Editar Persona</h1>
    <?php if (isset($mensaje)): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm"> <?= $mensaje ?> </div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
      <input name="nombre" value="<?= htmlspecialchars($persona['nombre']) ?>" class="w-full border px-3 py-2 rounded" required>
      <input name="dni" value="<?= htmlspecialchars($persona['dni']) ?>" class="w-full border px-3 py-2 rounded" required>
      <input name="cuit" value="<?= htmlspecialchars($persona['cuit']) ?>" class="w-full border px-3 py-2 rounded">
      <input name="telefono" value="<?= htmlspecialchars($persona['telefono']) ?>" class="w-full border px-3 py-2 rounded">
      <input name="email" type="email" value="<?= htmlspecialchars($persona['email']) ?>" class="w-full border px-3 py-2 rounded">
      <input name="foto_url" value="<?= htmlspecialchars($persona['foto_url']) ?>" class="w-full border px-3 py-2 rounded">
      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Actualizar</button>
    </form>
    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-gray-600 hover:underline">← Volver al listado</a>
    </div>
  </div>
</body>
</html>
