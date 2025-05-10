<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';

$habilitacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rol = isset($_GET['rol']) ? strtolower(trim($_GET['rol'])) : 'titular';
$label = ucfirst($rol);

$roles_permitidos = ['titular', 'chofer', 'celador'];
if (!in_array($rol, $roles_permitidos)) {
  die("Rol no válido.");
}

// Normalizar rol para guardar y comparar con consistencia
$rol_formateado = ($rol === 'chofer') ? 'CONDUCTOR' : ucfirst(strtolower($rol));


// Verificar si ya hay una persona con ese rol asignada
$check = $pdo->prepare("SELECT COUNT(*) FROM habilitaciones_personas WHERE habilitacion_id = ? AND LOWER(rol) = LOWER(?)");
$check->execute([$habilitacion_id, $rol_formateado]);
if ($check->fetchColumn()) {
  echo "<script>alert('Ya hay un $label cargado en esta habilitación.'); window.location.href='index.php';</script>";
  exit;
}

// Guardar datos si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $persona_id = $_POST['persona_id'] ?? 0;
  $licencia_categoria = $_POST['licencia_categoria'] ?? null;

  if ($persona_id && $habilitacion_id) {
    $query = "INSERT INTO habilitaciones_personas (habilitacion_id, persona_id, rol, licencia_categoria) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$habilitacion_id, $persona_id, $rol_formateado, $licencia_categoria]);
    header("Location: index.php?id=$habilitacion_id");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asignar <?= $label ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-xl">
    <div class="mb-6 text-center">
      <h2 class="text-2xl font-bold text-blue-700">Asignar <?= $label ?> a Habilitación</h2>
      <p class="text-sm text-gray-600">Habilitación ID: <?= $habilitacion_id ?></p>
    </div>

    <form method="POST">
      <input type="hidden" name="habilitacion_id" value="<?= $habilitacion_id ?>">

      <div class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Seleccionar persona registrada</label>
        <select name="persona_id" required class="w-full border border-gray-300 rounded px-3 py-2">
          <option value="">-- Seleccionar <?= strtolower($label) ?> --</option>
          <?php
          $stmt = $pdo->query("SELECT id, nombre, dni FROM personas ORDER BY nombre");
          while ($row = $stmt->fetch()) {
            echo "<option value='{$row['id']}'>{$row['nombre']} - DNI {$row['dni']}</option>";
          }
          ?>
        </select>
      </div>

      <?php if ($rol === 'chofer'): ?>
      <div class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Categoría de Licencia</label>
        <input type="text" name="licencia_categoria" placeholder="Ej: D1, D2" class="w-full border border-gray-300 rounded px-3 py-2">
      </div>
      <?php endif; ?>

      <div class="text-center">
        <button type="submit" class="bg-red-700 text-white px-4 py-2 rounded hover:bg-red-800">
          ✅ Asignar <?= $label ?>
        </button>
      </div>
    </form>
  </div>
</body>
</html>
