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

  <h1 class="text-3xl font-bold text-[#891628] text-center">
    Asociar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?>
  </h1>

  <form method="POST" action="procesar_asociar_establecimiento.php" class="space-y-6" id="form-establecimiento">
    <input type="hidden" name="habilitacion_id" value="<?= $habilitacion_id ?>">
    <input type="hidden" id="establecimiento_id" name="establecimiento_id">

    <!-- Buscador -->
    <div>
      <label class="block text-sm font-semibold mb-2">Buscar <?= $esRemis ? 'Remisería' : 'Establecimiento' ?>:</label>
      <input type="text" id="buscador" placeholder="Escribí nombre o dirección..." 
        class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-[#891628]" autocomplete="off">
    </div>

    <!-- Tarjeta visual -->
    <div id="tarjetaEstablecimiento" class="hidden mb-4"></div>

    <!-- Datos -->
    <div id="datosEstablecimiento" class="hidden space-y-4">
      <div>
        <label class="block text-sm font-semibold">Nombre</label>
        <input type="text" id="nombre" name="nombre" class="w-full border px-4 py-2 rounded-lg">
      </div>
      <div>
        <label class="block text-sm font-semibold">Domicilio</label>
        <input type="text" id="domicilio" name="domicilio" class="w-full border px-4 py-2 rounded-lg">
      </div>
      <div>
        <label class="block text-sm font-semibold">Localidad</label>
        <input type="text" id="localidad" name="localidad" class="w-full border px-4 py-2 rounded-lg" placeholder="Ej: Lanús">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold">Latitud</label>
          <input type="text" id="latitud" name="latitud" class="w-full border px-4 py-2 rounded-lg" readonly>
        </div>
        <div>
          <label class="block text-sm font-semibold">Longitud</label>
          <input type="text" id="longitud" name="longitud" class="w-full border px-4 py-2 rounded-lg" readonly>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold">Dirección aproximada</label>
        <textarea id="direccion" name="direccion" class="w-full border px-4 py-2 rounded-lg"></textarea>
      </div>
    </div>

    <!-- Mapa -->
    <div id="map" class="w-full h-[300px] rounded-lg border shadow-inner mt-4"></div>

    <!-- Botón -->
    <div class="text-center">
      <button type="submit" class="bg-[#891628] hover:bg-red-800 text-white px-8 py-3 rounded-lg font-semibold">
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
const establecimientoId = document.getElementById('establecimiento_id');
const datosEstablecimiento = document.getElementById('datosEstablecimiento');
const tarjetaEstablecimiento = document.getElementById('tarjetaEstablecimiento');
const nombre = document.getElementById('nombre');
const domicilio = document.getElementById('domicilio');
const localidad = document.getElementById('localidad');
const latitud = document.getElementById('latitud');
const longitud = document.getElementById('longitud');
const direccion = document.getElementById('direccion');
let marker = null;

// Función para mostrar tarjeta
function mostrarTarjeta(data) {
  tarjetaEstablecimiento.innerHTML = `
    <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 shadow-sm">
      <h3 class="font-bold text-[#891628] text-lg mb-2">🏫 ${data.nombre}</h3>
      <p class="text-sm text-gray-700">📍 ${data.domicilio}${data.localidad ? ', ' + data.localidad : ''}</p>
      ${data.latitud && data.longitud ? `<p class="text-xs text-gray-500 mt-2">🌎 Lat: ${data.latitud} | Lng: ${data.longitud}</p>` : ''}
    </div>
  `;
  tarjetaEstablecimiento.classList.remove('hidden');
}

// Función para limpiar campos
function limpiarCampos() {
  establecimientoId.value = '';
  nombre.value = '';
  domicilio.value = '';
  localidad.value = '';
  latitud.value = '';
  longitud.value = '';
  direccion.value = '';
  tarjetaEstablecimiento.classList.add('hidden');
}

// Buscar y completar automáticamente
buscador.addEventListener('input', function() {
  const filtro = buscador.value.trim().toLowerCase();
  if (filtro.length < 2) {
    limpiarCampos();
    datosEstablecimiento.classList.add('hidden');
    return;
  }

  const resultado = opciones.find(op => 
    (op.nombre && op.nombre.toLowerCase().includes(filtro)) ||
    (op.domicilio && op.domicilio.toLowerCase().includes(filtro))
  );

  if (resultado) {
    establecimientoId.value = resultado.id;
    nombre.value = resultado.nombre || '';
    domicilio.value = resultado.domicilio || '';
    localidad.value = resultado.localidad || '';
    latitud.value = resultado.latitud || '';
    longitud.value = resultado.longitud || '';
    direccion.value = resultado.direccion || '';

    mostrarTarjeta(resultado);
    datosEstablecimiento.classList.remove('hidden');
    if (resultado.latitud && resultado.longitud) {
      actualizarMapa(parseFloat(resultado.latitud), parseFloat(resultado.longitud));
    }
  } else {
    limpiarCampos();
    datosEstablecimiento.classList.remove('hidden');
  }
});

// MAPA
const map = L.map('map').setView([-34.7000, -58.4000], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

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
    if (!localidad.value) {
      localidad.value = "Lanús";
    }
  } catch (error) {
    Swal.fire({
      icon: 'warning',
      title: 'No se pudo obtener la dirección',
      text: 'Podés completar la dirección manualmente.',
    });
    direccion.value = '';
  }
});

function actualizarMapa(lat, lng) {
  map.setView([lat, lng], 16);
  if (marker) {
    marker.setLatLng([lat, lng]);
  } else {
    marker = L.marker([lat, lng]).addTo(map);
  }
}
</script>

</body>
</html>
