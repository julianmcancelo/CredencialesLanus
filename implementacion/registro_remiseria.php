<?php
require_once 'conexion.php';
session_start();

// ✅ Verificación de sesión y rol admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  echo "<!DOCTYPE html>
  <html lang='es'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Acceso Restringido</title>
    <link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>
  </head>
  <body class='flex items-center justify-center min-h-screen bg-red-50 px-4'>
    <div class='max-w-md w-full bg-white border border-red-300 rounded-xl shadow-lg p-8 text-center space-y-4'>
      <div class='text-red-500 text-5xl'>🚫</div>
      <h1 class='text-2xl font-bold text-red-800'>Acceso restringido</h1>
      <p class='text-gray-700'>Esta sección es solo para administradores. <a href='login.php' class='text-blue-600 underline font-medium'>Iniciar sesión</a></p>
      <p class='text-sm text-gray-500 border-t pt-3'>Si necesitás ayuda, escribinos a <strong>movilidadytransporte@lanus.gob.ar</strong></p>
    </div>
  </body>
  </html>";
  exit;
}

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = $_POST['nombre'];
  $direccion = $_POST['direccion'];
  $localidad = $_POST['localidad'];
  $lat = $_POST['lat'];
  $lng = $_POST['lng'];
  $nro_habilitacion = $_POST['nro_habilitacion'];
  $nro_expediente = $_POST['nro_expediente'];

  $stmt = $pdo->prepare("INSERT INTO remiserias (nombre, direccion, localidad, latitud, longitud, nro_habilitacion, nro_expediente) VALUES (?, ?, ?, ?, ?, ?, ?)");
  if ($stmt->execute([$nombre, $direccion, $localidad, $lat, $lng, $nro_habilitacion, $nro_expediente])) {
    $mensaje = "✅ Remisería registrada correctamente.";
  } else {
    $mensaje = "❌ Error al registrar remisería.";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Remisería</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-3xl bg-white p-6 rounded-xl shadow-lg">
    <h1 class="text-2xl font-bold text-[#891628] mb-4 text-center">Registrar Remisería</h1>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-center text-sm"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="text" name="nombre" placeholder="Nombre de la remisería" required class="border px-3 py-2 rounded">
      <input type="text" name="direccion" placeholder="Dirección" required class="border px-3 py-2 rounded">
      <input type="text" name="localidad" placeholder="Localidad" required class="border px-3 py-2 rounded">
      <input type="text" name="nro_habilitacion" placeholder="N° de Habilitación" required class="border px-3 py-2 rounded">
      <input type="text" name="nro_expediente" placeholder="N° de Expediente" required class="border px-3 py-2 rounded">

      <input type="hidden" name="lat" id="lat" required>
      <input type="hidden" name="lng" id="lng" required>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Ubicación en el mapa:</label>
        <div id="map" class="w-full h-64 rounded border"></div>
      </div>

      <div class="md:col-span-2">
        <button type="submit" class="w-full bg-[#891628] text-white py-2 rounded hover:bg-red-800 font-semibold">Registrar Remisería</button>
      </div>
    </form>

    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-blue-700 hover:underline">← Volver al Panel</a>
    </div>
  </div>

  <script>
    const map = L.map('map').setView([-34.708, -58.395], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([-34.708, -58.395], { draggable: true }).addTo(map);
    document.getElementById('lat').value = marker.getLatLng().lat;
    document.getElementById('lng').value = marker.getLatLng().lng;

    marker.on('dragend', function(e) {
      const pos = marker.getLatLng();
      document.getElementById('lat').value = pos.lat;
      document.getElementById('lng').value = pos.lng;
    });
  </script>
</body>
</html>
