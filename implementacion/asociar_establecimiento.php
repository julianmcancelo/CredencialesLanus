<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'conexion.php';

$mensaje = null;
$habilitacion_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['habilitacion_id']) ? intval($_POST['habilitacion_id']) : 0);
$tipoTransporte = null;
$esRemis = false;

// Verificar el tipo de transporte
if ($habilitacion_id > 0) {
  $stmtTipo = $pdo->prepare("SELECT tipo_transporte FROM habilitaciones_generales WHERE id = ?");
  $stmtTipo->execute([$habilitacion_id]);
  $tipoTransporte = $stmtTipo->fetchColumn();
  $esRemis = strtoupper((string)$tipoTransporte) === 'REMIS';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $establecimiento_id = $_POST['establecimiento_id'] ?? null;

  if ($habilitacion_id && $establecimiento_id) {
    $stmt = $pdo->prepare("INSERT INTO habilitaciones_establecimientos (habilitacion_id, establecimiento_id, tipo) VALUES (?, ?, ?)");
    if ($stmt->execute([$habilitacion_id, $establecimiento_id, $esRemis ? 'remiseria' : 'establecimiento'])) {
      $mensaje = $esRemis ? "✅ Remisería asociada correctamente." : "✅ Establecimiento asociado correctamente.";
    } else {
      $mensaje = "❌ Error al asociar el destino.";
    }
  }
}

// Obtener listado de opciones
if ($esRemis) {
  $stmt = $pdo->query("SELECT id, nombre, direccion AS domicilio, '' AS localidad FROM remiserias ORDER BY nombre ASC");
} else {
  $stmt = $pdo->query("SELECT id, nombre, domicilio, localidad FROM establecimientos ORDER BY nombre ASC");
}
$opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asociar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-xl bg-white p-6 rounded-xl shadow-lg">
    <h1 class="text-2xl font-bold text-blue-700 text-center mb-4">
      Asociar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?>
    </h1>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm text-center">
        <?= $mensaje ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <input type="hidden" name="habilitacion_id" value="<?= $habilitacion_id ?>">

      <label class="block text-sm font-semibold text-gray-700">
        Seleccionar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?>
      </label>
      <select name="establecimiento_id" required class="w-full border border-gray-300 px-3 py-2 rounded shadow-sm">
        <option value="" disabled selected>-- Selecciona uno --</option>
        <?php foreach ($opciones as $item): ?>
          <option value="<?= $item['id'] ?>">
            <?= htmlspecialchars($item['nombre'] . " - " . $item['domicilio'] . ($item['localidad'] ? ", " . $item['localidad'] : '')) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="text-center">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
          Asociar
        </button>
      </div>
    </form>

    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-blue-600 hover:underline">&larr; Volver al Panel</a>
    </div>
  </div>
</body>
</html>
