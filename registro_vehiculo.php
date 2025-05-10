<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'conexion.php';
session_start();

// Verificar si el usuario inició sesión y tiene rol válido
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
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
      <p class='text-gray-700'>Esta sección está reservada para usuarios administradores. Por favor, <a href='login.php' class='text-blue-600 underline font-medium'>iniciá sesión</a> con una cuenta autorizada.</p>
      <div class='text-sm text-gray-600 border-t pt-4'>
        <p>Si sos lector invitado, podés visualizar tu credencial digital desde el enlace que te enviamos o solicitá asistencia.</p>
        <p class='mt-1 font-semibold text-red-900'>📧 movilidadytransporte@lanus.gob.ar</p>
      </div>
    </div>
  </body>
  </html>";
  exit;
}

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dominio = $_POST['dominio'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $motor = $_POST['motor'];
    $asientos = $_POST['asientos'];
    $inscripcion_inicial = $_POST['inscripcion_inicial'];
    $habilitacion_id = $_POST['habilitacion_id'];

    $stmt = $pdo->prepare("INSERT INTO vehiculos (dominio, marca, modelo, motor, asientos, inscripcion_inicial) 
                           VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$dominio, $marca, $modelo, $motor, $asientos, $inscripcion_inicial])) {
        $vehiculo_id = $pdo->lastInsertId();
        $stmt_habilitacion = $pdo->prepare("INSERT INTO habilitaciones_vehiculos (habilitacion_id, vehiculo_id) VALUES (?, ?)");
        $stmt_habilitacion->execute([$habilitacion_id, $vehiculo_id]);
        $mensaje = "✅ Vehículo registrado y asociado a la habilitación con éxito.";
    } else {
        $mensaje = "❌ Error al registrar el vehículo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Vehículo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-2xl bg-white p-6 rounded-xl shadow-lg">
    <h1 class="text-2xl font-bold text-[#891628] mb-4 text-center">Registrar Vehículo</h1>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm text-center"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="habilitacion_id" value="<?= $_GET['habilitacion_id'] ?? '' ?>">

      <div>
        <label class="block text-sm text-gray-700 font-semibold mb-1">Dominio</label>
        <input name="dominio" required class="w-full border border-gray-300 px-3 py-2 rounded shadow-sm">
      </div>

      <div>
        <label class="block text-sm text-gray-700 font-semibold mb-1">Marca</label>
        <input name="marca" class="w-full border border-gray-300 px-3 py-2 rounded shadow-sm">
      </div>

      <div>
        <label class="block text-sm text-gray-700 font-semibold mb-1">Modelo</label>
        <input name="modelo" class="w-full border border-gray-300 px-3 py-2 rounded shadow-sm">
      </div>

      <div>
        <label class="block text-sm text-gray-700 font-semibold mb-1">Motor</label>
        <input name="motor" class="w-full border border-gray-300 px-3 py-2 rounded shadow-sm">
      </div>

      <div>
        <label class="block text-sm text-gray-700 font-semibold mb-1">Cantidad de Asientos</label>
        <input type="number" name="asientos" class="w-full border border-gray-300 px-3 py-2 rounded shadow-sm">
      </div>

      <div>
        <label class="block text-sm text-gray-700 font-semibold mb-1">Fecha de Inscripción Inicial</label>
        <input type="date" name="inscripcion_inicial" class="w-full border border-gray-300 px-3 py-2 rounded shadow-sm">
      </div>

      <div class="md:col-span-2 flex justify-end gap-2">
        <a href="index.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">Cancelar</a>
        <button type="submit" class="bg-red-700 text-white px-6 py-2 rounded hover:bg-red-800 font-semibold">Registrar Vehículo</button>
      </div>
    </form>

    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-blue-700 hover:underline">← Volver al Panel</a>
    </div>
  </div>
</body>
</html>
