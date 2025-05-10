<?php
session_start();
require_once 'conexion.php';

// Acceso restringido solo para administradores
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  echo "<!DOCTYPE html>
  <html lang='es'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Acceso Restringido</title>
    <link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>
  </head>
  <body class='flex items-center justify-center min-h-screen bg-gradient-to-br from-red-100 to-red-200 px-4'>
    <div class='bg-white max-w-md w-full rounded-xl shadow-lg p-6 text-center border border-red-300'>
      <div class='text-5xl text-red-500 mb-4'>🔒</div>
      <h2 class='text-xl font-bold text-red-700 mb-2'>Acceso restringido</h2>
      <p class='text-gray-700 mb-4'>Esta sección está reservada para usuarios administradores.</p>
      <a href='login.php' class='text-blue-600 underline font-semibold'>Iniciar sesión</a>
      <p class='text-sm text-gray-500 mt-4'>¿Sos lector invitado? Contactate:<br>📧 movilidadytransporte@lanus.gob.ar</p>
    </div>
  </body>
  </html>";
  exit;
}

require_once 'conexion.php';
$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $domicilio = $_POST['domicilio'];
    $localidad = $_POST['localidad'];
    $latitud = $_POST['latitud'];
    $longitud = $_POST['longitud'];
    $direccion = $_POST['direccion'];

    try {
        $stmt = $pdo->prepare("INSERT INTO establecimientos (nombre, domicilio, localidad, latitud, longitud, direccion) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nombre, $domicilio, $localidad, $latitud, $longitud, $direccion])) {
            $mensaje = "✅ Establecimiento registrado con éxito.";
        } else {
            $mensaje = "❌ Error al registrar el establecimiento.";
        }
    } catch (PDOException $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Establecimiento</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gradient-to-tr from-gray-100 via-white to-gray-100 min-h-screen text-gray-800">
  <header class="bg-[#00adee] text-white p-4 shadow-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-2xl font-bold tracking-wide">Registrar Establecimiento</h1>
      <a href="index.php" class="text-sm underline hover:text-gray-200">Volver</a>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-4 py-6">
    <?php if ($mensaje): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-4 shadow">
        <?= $mensaje ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6 bg-white p-6 rounded-xl shadow-lg">
      <div>
        <label class="block text-sm font-semibold mb-1">Nombre del establecimiento</label>
        <input name="nombre" required class="w-full border px-4 py-2 rounded-md focus:ring focus:ring-blue-200 shadow-sm">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Domicilio</label>
          <input name="domicilio" required class="w-full border px-4 py-2 rounded-md shadow-sm focus:ring focus:ring-blue-200">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Localidad</label>
          <input name="localidad" required class="w-full border px-4 py-2 rounded-md shadow-sm focus:ring focus:ring-blue-200">
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-2">Seleccioná la ubicación en el mapa</label>
        <div id="map" class="w-full h-64 rounded-md border border-gray-300 shadow-inner"></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Latitud</label>
          <input name="latitud" id="latitud" required readonly class="w-full border px-3 py-2 rounded-md shadow-sm bg-gray-100">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Longitud</label>
          <input name="longitud" id="longitud" required readonly class="w-full border px-3 py-2 rounded-md shadow-sm bg-gray-100">
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Dirección aproximada</label>
        <textarea name="direccion" id="direccion" class="w-full border px-3 py-2 rounded-md shadow-sm bg-gray-100" rows="2" readonly></textarea>
      </div>
      <div class="flex justify-end gap-2 pt-4">
        <a href="index.php" class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">Cancelar</a>
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2 rounded-md font-semibold transition">Registrar</button>
      </div>
    </form>
  </main>

  <script>
    const map = L.map('map').setView([-34.700, -58.400], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker;

    map.on('click', async function (e) {
      const { lat, lng } = e.latlng;
      document.getElementById('latitud').value = lat.toFixed(8);
      document.getElementById('longitud').value = lng.toFixed(8);

      if (marker) {
        marker.setLatLng(e.latlng);
      } else {
        marker = L.marker(e.latlng).addTo(map);
      }

      try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await res.json();
        document.getElementById('direccion').value = data.display_name || 'Dirección no encontrada';
      } catch (error) {
        document.getElementById('direccion').value = 'Error al obtener la dirección';
      }
    });
  </script>
</body>
</html>
