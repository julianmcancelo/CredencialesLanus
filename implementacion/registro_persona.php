<?php
require_once 'conexion.php';
session_start();

// ✅ Verificar que solo accedan administradores
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
  try {
    $sql = "INSERT INTO personas (nombre, dni, cuit, telefono, email, foto_url) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      $_POST['nombre'],
      $_POST['dni'],
      $_POST['cuit'],
      $_POST['telefono'],
      $_POST['email'],
      $_POST['foto_url']
    ]);
    $mensaje = "✅ Persona registrada con éxito.";
  } catch (PDOException $e) {
    $mensaje = "❌ Error al registrar: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Persona</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-tr from-gray-100 via-white to-gray-100 min-h-screen text-gray-800">
  <header class="bg-[#00adee] text-white p-4 shadow-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-2xl font-bold tracking-wide">Registrar Persona</h1>
      <div class="space-x-2">
        <a href="registro_vehiculo.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg shadow hover:bg-gray-100 font-medium">+ Vehículo</a>
        <a href="formulario_datos_generales.php" class="bg-white text-green-600 px-4 py-2 rounded-lg shadow hover:bg-gray-100 font-medium">+ Nueva Habilitación</a>
      </div>
    </div>
  </header>

  <div class="max-w-3xl mx-auto px-4 py-6">
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold text-[#891628]">Registrar Persona</h2>
      <p class="text-sm text-gray-600">Completá los datos para cargar una nueva persona al sistema</p>
    </div>

    <?php if (isset($mensaje)): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm text-center shadow"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-6 rounded-lg shadow">
      <div>
        <label class="block text-sm font-semibold mb-1">Nombre completo</label>
        <input name="nombre" required class="w-full border px-3 py-2 rounded shadow-sm focus:ring-2 focus:ring-[#00adee]">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">DNI</label>
        <input name="dni" required class="w-full border px-3 py-2 rounded shadow-sm focus:ring-2 focus:ring-[#00adee]">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">CUIT</label>
        <input name="cuit" class="w-full border px-3 py-2 rounded shadow-sm focus:ring-2 focus:ring-[#00adee]">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Teléfono</label>
        <input name="telefono" class="w-full border px-3 py-2 rounded shadow-sm focus:ring-2 focus:ring-[#00adee]">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Email</label>
        <input name="email" type="email" class="w-full border px-3 py-2 rounded shadow-sm focus:ring-2 focus:ring-[#00adee]">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">URL de Foto</label>
        <input name="foto_url" class="w-full border px-3 py-2 rounded shadow-sm focus:ring-2 focus:ring-[#00adee]">
      </div>

      <div class="md:col-span-2 text-center">
        <button type="submit" class="w-full bg-[#891628] text-white py-2 rounded-lg hover:bg-red-800 font-semibold">Registrar Persona</button>
      </div>
    </form>

    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-blue-600 hover:underline">← Volver al Panel</a>
    </div>
  </div>
</body>
</html>
