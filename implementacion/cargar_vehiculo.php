<?php
require_once 'conexion.php';
session_start();

// Verificar acceso solo admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  echo "<!DOCTYPE html>
  <html lang='es'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Acceso Restringido</title>
    <link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>
  </head>
  <body class='flex items-center justify-center min-h-screen bg-gradient-to-br from-red-50 to-red-100 px-4'>
    <div class='max-w-lg w-full bg-white border border-red-300 rounded-xl shadow-lg p-8 text-center space-y-4'>
      <div class='text-red-500 text-5xl'>🔒</div>
      <h1 class='text-2xl font-bold text-red-800'>Acceso restringido</h1>
      <p class='text-gray-700'>Esta sección está reservada para administradores. <a href='login.php' class='text-blue-600 underline font-medium'>Iniciá sesión</a> con una cuenta válida.</p>
      <div class='text-sm text-gray-600 border-t pt-4'>
        <p>¿Sos lector invitado? Podés usar tu credencial desde el enlace enviado o pedir asistencia.</p>
        <p class='mt-1 font-semibold text-red-900'>📧 movilidadytransporte@lanus.gob.ar</p>
      </div>
    </div>
  </body>
  </html>";
  exit;
}

$mensaje = null;

// Detectar habilitación desde GET
$habilitacion_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;

if (!$habilitacion_id) {
  die("ID de habilitación inválido o no proporcionado.");
}

// Obtener número de licencia de la habilitación
$stmt = $pdo->prepare("SELECT nro_licencia FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$habilitacion_id]);
$habilitacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$habilitacion) {
  die("Habilitación no encontrada.");
}

// Obtener los vehículos registrados
$stmt = $pdo->prepare("SELECT id, dominio, marca, modelo FROM vehiculos");
$stmt->execute();
$vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['vehiculo_id'])) {
    $vehiculo_id = $_POST['vehiculo_id'];

    $stmt = $pdo->prepare("INSERT INTO habilitaciones_vehiculos (habilitacion_id, vehiculo_id) VALUES (?, ?)");
    $stmt->execute([$habilitacion_id, $vehiculo_id]);

    $mensaje = "✅ Vehículo asociado correctamente a la habilitación.";
  } else {
    $mensaje = "❌ Error: Seleccione un vehículo.";
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asociar Vehículo a Habilitación</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <header class="bg-[#00adee] text-white p-4 shadow">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-2xl font-bold tracking-wide">#LaCiudadQueNosMerecemos</h1>
      <a href="index.php" class="text-white hover:text-gray-200">← Volver al Panel</a>
    </div>
  </header>

  <div class="max-w-4xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-xl">
    <h2 class="text-3xl font-bold text-[#891628] mb-6 text-center">Asociar Vehículo a Habilitación</h2>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-4 shadow text-center">
        <?= $mensaje ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Habilitación</label>
        <input type="text" value="<?= htmlspecialchars($habilitacion['nro_licencia']) ?>" disabled class="w-full border border-gray-300 px-4 py-2 rounded shadow-sm bg-gray-100">
        <input type="hidden" name="habilitacion_id" value="<?= $habilitacion_id ?>">
      </div>

      <div>
        <label for="vehiculo_id" class="block text-gray-700 font-medium mb-1">Selecciona un Vehículo</label>
        <select name="vehiculo_id" id="vehiculo_id" class="w-full border border-gray-300 px-4 py-2 rounded shadow-sm" required>
          <option value="" disabled selected>Selecciona un vehículo</option>
          <?php foreach ($vehiculos as $vehiculo): ?>
            <option value="<?= $vehiculo['id'] ?>"><?= $vehiculo['dominio'] ?> - <?= $vehiculo['marca'] ?> <?= $vehiculo['modelo'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex justify-end gap-4">
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</a>
        <button type="submit" class="px-6 py-2 bg-[#891628] text-white rounded hover:bg-red-800 font-semibold shadow">Guardar</button>
      </div>
    </form>
  </div>

</body>
</html>
