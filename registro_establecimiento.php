<?php
session_start();
require_once 'conexion.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  echo "<script>window.location.href='login.php';</script>";
  exit;
}

$mensaje = null;
$habilitacion_id = $_GET['id'] ?? $_POST['habilitacion_id'] ?? 0;
$habilitacion_id = intval($habilitacion_id);

if (!$habilitacion_id) {
  die("ID de habilitación inválido.");
}

// Consultar tipo transporte
$stmt = $pdo->prepare("SELECT tipo_transporte FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$habilitacion_id]);
$tipoTransporte = $stmt->fetchColumn();
$esRemis = strtoupper((string)$tipoTransporte) === 'REMIS';

// Guardar datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $domicilio = trim($_POST['domicilio'] ?? '');
  $localidad = trim($_POST['localidad'] ?? '');
  $latitud = trim($_POST['latitud'] ?? '');
  $longitud = trim($_POST['longitud'] ?? '');
  $direccion = trim($_POST['direccion'] ?? '');
  $establecimiento_id = $_POST['establecimiento_id'] ?? null;

  if (!$nombre || !$domicilio) {
    $_SESSION['mensaje_error'] = "Faltan completar campos obligatorios.";
    header("Location: asociar_establecimiento.php?id=$habilitacion_id");
    exit;
  }

  try {
    if ($establecimiento_id) {
      // Actualizar existente
      if ($esRemis) {
        $stmt = $pdo->prepare("UPDATE remiserias SET nombre = ?, direccion = ?, latitud = ?, longitud = ? WHERE id = ?");
        $stmt->execute([$nombre, $domicilio, $latitud, $longitud, $establecimiento_id]);
      } else {
        $stmt = $pdo->prepare("UPDATE establecimientos SET nombre = ?, domicilio = ?, localidad = ?, latitud = ?, longitud = ?, direccion = ? WHERE id = ?");
        $stmt->execute([$nombre, $domicilio, $localidad, $latitud, $longitud, $direccion, $establecimiento_id]);
      }
    } else {
      // Crear nuevo
      if ($esRemis) {
        $stmt = $pdo->prepare("INSERT INTO remiserias (nombre, direccion, latitud, longitud) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $domicilio, $latitud, $longitud]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO establecimientos (nombre, domicilio, localidad, latitud, longitud, direccion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $domicilio, $localidad, $latitud, $longitud, $direccion]);
      }
      $establecimiento_id = $pdo->lastInsertId();
    }

    // Asociar
    $stmt = $pdo->prepare("INSERT INTO habilitaciones_establecimientos (habilitacion_id, establecimiento_id, tipo) VALUES (?, ?, ?)");
    $stmt->execute([$habilitacion_id, $establecimiento_id, $esRemis ? 'remiseria' : 'establecimiento']);

    $_SESSION['mensaje_exito'] = $esRemis ? "Remisería asociada exitosamente." : "Establecimiento asociado exitosamente.";
    header("Location: asociar_establecimiento.php?id=$habilitacion_id");
    exit;
  } catch (PDOException $e) {
    $_SESSION['mensaje_error'] = "Error en la base de datos: " . $e->getMessage();
    header("Location: asociar_establecimiento.php?id=$habilitacion_id");
    exit;
  }
}

// Traer opciones para autocompletar
if ($esRemis) {
  $stmt = $pdo->query("SELECT id, nombre, direccion AS domicilio, latitud, longitud FROM remiserias ORDER BY nombre");
} else {
  $stmt = $pdo->query("SELECT id, nombre, domicilio, localidad, latitud, longitud, direccion FROM establecimientos ORDER BY nombre");
}
$opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Asociar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-2xl space-y-8">

  <h1 class="text-3xl font-bold text-[#891628] text-center">Asociar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?></h1>

  <form method="POST" class="space-y-6" id="formulario">
    <input type="hidden" name="habilitacion_id" value="<?= $habilitacion_id ?>">
    <input type="hidden" name="establecimiento_id" id="establecimiento_id">

    <div>
      <label class="block mb-2 text-sm font-semibold">Buscar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?>:</label>
      <input type="text" id="buscador" class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-[#891628]" placeholder="Buscar nombre o domicilio...">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="block mb-2 text-sm font-semibold">Nombre:</label>
        <input type="text" name="nombre" id="nombre" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
      </div>
      <div>
        <label class="block mb-2 text-sm font-semibold">Domicilio:</label>
        <input type="text" name="domicilio" id="domicilio" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
      </div>
      <?php if (!$esRemis): ?>
      <div>
        <label class="block mb-2 text-sm font-semibold">Localidad:</label>
        <input type="text" name="localidad" id="localidad" class="w-full border border-gray-300 rounded-lg px-4 py-2">
      </div>
      <?php endif; ?>
    </div>

    <div id="map" class="w-full h-64 rounded-md border"></div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="block mb-2 text-sm font-semibold">Latitud:</label>
        <input type="text" name="latitud" id="latitud" readonly class="w-full bg-gray-100 border border-gray-300 px-4 py-2 rounded-lg">
      </div>
      <div>
        <label class="block mb-2 text-sm font-semibold">Longitud:</label>
        <input type="text" name="longitud" id="longitud" readonly class="w-full bg-gray-100 border border-gray-300 px-4 py-2 rounded-lg">
      </div>
    </div>

    <?php if (!$esRemis): ?>
    <div>
      <label class="block mb-2 text-sm font-semibold">Dirección aproximada:</label>
      <textarea name="direccion" id="direccion" class="w-full bg-gray-100 border border-gray-300 px-4 py-2 rounded-lg" rows="2" readonly></textarea>
    </div>
    <?php endif; ?>

    <div class="text-center pt-6">
      <button type="submit" class="bg-[#891628] hover:bg-red-800 text-white font-bold px-8 py-3 rounded-lg shadow transition">
        Asociar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?>
      </button>
    </div>
  </form>

  <div class="text-center">
    <a href="index.php" class="text-[#891628] hover:underline mt-4 inline-block text-sm font-medium">← Volver al Panel</a>
  </div>
</div>

<script>
const opciones = <?= json_encode($opciones) ?>;
const buscador = document.getElementById('buscador');
const nombre = document.getElementById('nombre');
const domicilio = document.getElementById('domicilio');
const localidad = document.getElementById('localidad');
const establecimientoId = document.getElementById('establecimiento_id');
const direccion = document.getElementById('direccion');
const latitud = document.getElementById('latitud');
const longitud = document.getElementById('longitud');

buscador.addEventListener('input', function() {
    const filtro = this.value.toLowerCase();
    const encontrado = opciones.find(opt => opt.nombre.toLowerCase().includes(filtro) || (opt.domicilio && opt.domicilio.toLowerCase().includes(filtro)));
    if (encontrado) {
        establecimientoId.value = encontrado.id;
        nombre.value = encontrado.nombre;
        domicilio.value = encontrado.domicilio;
        if (localidad) localidad.value = encontrado.localidad || '';
        if (direccion) direccion.value = encontrado.direccion || '';
        if (latitud) latitud.value = encontrado.latitud || '';
        if (longitud) longitud.value = encontrado.longitud || '';
    } else {
        establecimientoId.value = '';
        nombre.value = '';
        domicilio.value = '';
        if (localidad) localidad.value = '';
        if (direccion) direccion.value = '';
        if (latitud) latitud.value = '';
        if (longitud) longitud.value = '';
    }
});

// Mapa Leaflet
const map = L.map('map').setView([-34.700, -58.400], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let marker;
map.on('click', async function(e) {
    const { lat, lng } = e.latlng;
    latitud.value = lat.toFixed(8);
    longitud.value = lng.toFixed(8);

    if (marker) {
        marker.setLatLng(e.latlng);
    } else {
        marker = L.marker(e.latlng).addTo(map);
    }

    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await res.json();
        direccion.value = data.display_name || '';
    } catch (error) {
        direccion.value = 'No se pudo obtener dirección';
    }
}

// Mensajes SweetAlert
<?php if (isset($_SESSION['mensaje_exito'])): ?>
Swal.fire('Éxito', '<?= $_SESSION['mensaje_exito'] ?>', 'success');
<?php unset($_SESSION['mensaje_exito']); ?>
<?php elseif (isset($_SESSION['mensaje_error'])): ?>
Swal.fire('Error', '<?= $_SESSION['mensaje_error'] ?>', 'error');
<?php unset($_SESSION['mensaje_error']); ?>
<?php endif; ?>
</script>

</body>
</html>
