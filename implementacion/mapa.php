<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';

$datos = [];

try {
  $stmt = $pdo->query("SELECT e.id, e.nombre, e.latitud, e.longitud,
    COUNT(hg.id) AS total_licencias,
    GROUP_CONCAT(DISTINCT hg.nro_licencia SEPARATOR ', ') AS licencias,
    GROUP_CONCAT(DISTINCT hg.estado SEPARATOR ', ') AS estados,
    GROUP_CONCAT(DISTINCT hg.tipo_transporte SEPARATOR ', ') AS tipos
    FROM establecimientos e
    LEFT JOIN habilitaciones_establecimientos he ON he.establecimiento_id = e.id AND he.tipo = 'establecimiento'
    LEFT JOIN habilitaciones_generales hg ON hg.id = he.habilitacion_id
    GROUP BY e.id");

  foreach ($stmt->fetchAll() as $row) {
    $row['tipo'] = 'educacion';
    $datos[] = $row;
  }

  $stmt = $pdo->query("SELECT r.id, r.nombre, r.latitud, r.longitud,
    COUNT(hg.id) AS total_licencias,
    GROUP_CONCAT(DISTINCT hg.nro_licencia SEPARATOR ', ') AS licencias,
    GROUP_CONCAT(DISTINCT hg.estado SEPARATOR ', ') AS estados,
    GROUP_CONCAT(DISTINCT hg.tipo_transporte SEPARATOR ', ') AS tipos
    FROM remiserias r
    LEFT JOIN habilitaciones_establecimientos he ON he.establecimiento_id = r.id AND he.tipo = 'remiseria'
    LEFT JOIN habilitaciones_generales hg ON hg.id = he.habilitacion_id
    GROUP BY r.id");

  foreach ($stmt->fetchAll() as $row) {
    $row['tipo'] = 'remises';
    $datos[] = $row;
  }

} catch (PDOException $e) {
  die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mapa de Lanús</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen/Control.FullScreen.css" />
  <style>
    #map { height: 85vh; }
    .popup-info { font-size: 0.875rem; line-height: 1.25rem; }
    .popup-info strong { color: #1f2937; }
    .legend i {
      width: 20px;
      height: 20px;
      display: inline-block;
      margin-right: 8px;
      background-size: cover;
      vertical-align: middle;
    }
  </style>
</head>
<body class="bg-gray-100">

<header class="bg-red-800 text-white py-4 shadow">
  <h1 class="text-center text-2xl font-semibold">🗺️ Mapa de Establecimientos Educativos y Remiserías en Lanús</h1>
</header>

<main class="p-4">
  <div id="map" class="rounded-lg shadow border border-gray-300"></div>
</main>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.fullscreen/Control.FullScreen.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

<script>
var map = L.map('map', { fullscreenControl: true }).setView([-34.7061, -58.3972], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: 'Map data © OpenStreetMap contributors'
}).addTo(map);

var iconos = {
  educacion: L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/167/167707.png', iconSize: [30,30], iconAnchor: [15,30], popupAnchor: [0,-30] }),
  remises: L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png', iconSize: [30,30], iconAnchor: [15,30], popupAnchor: [0,-30] })
};

var datos = <?php echo json_encode($datos); ?>;

var grupoEducacion = L.layerGroup();
var grupoRemises = L.layerGroup();
var heatEducacion = [];
var heatRemises = [];

datos.forEach(function(p) {
  var lat = parseFloat(p.latitud);
  var lng = parseFloat(p.longitud);
  var icon = iconos[p.tipo];

  let html = `<div class='popup-info'><strong>${p.nombre}</strong><br>`;
  html += `<strong>Tipo:</strong> ${p.tipo === 'educacion' ? 'Establecimiento educativo' : 'Remisería'}<br>`;
  html += `<strong>Licencias:</strong> ${p.total_licencias || 0}<br>`;
  html += `<strong>Nº de licencias:</strong> ${p.licencias || 'Ninguna'}<br>`;
  html += `<strong>Estados:</strong> ${p.estados || 'N/A'}<br>`;
  html += `<strong>Tipo de habilitación:</strong> ${p.tipos || 'N/A'}<br>`;
  html += `</div>`;

  var marker = L.marker([lat, lng], { icon: icon }).bindPopup(html);

  if (p.tipo === 'educacion') {
    grupoEducacion.addLayer(marker);
    heatEducacion.push([lat, lng, parseInt(p.total_licencias) || 1]);
  } else {
    grupoRemises.addLayer(marker);
    heatRemises.push([lat, lng, parseInt(p.total_licencias) || 1]);
  }
});

grupoEducacion.addTo(map);
grupoRemises.addTo(map);

var heatLayerEducacion = L.heatLayer(heatEducacion, {
  radius: 25,
  blur: 15,
  maxZoom: 17,
  gradient: {
    0.2: '#a3c9f1',
    0.4: '#69a6e3',
    0.6: '#407ec9',
    0.8: '#2b5fa5',
    1.0: '#183f80'
  }
});

var heatLayerRemises = L.heatLayer(heatRemises, {
  radius: 25,
  blur: 15,
  maxZoom: 17,
  gradient: {
    0.2: '#ffffb2',
    0.4: '#fecc5c',
    0.6: '#fd8d3c',
    0.8: '#f03b20',
    1.0: '#bd0026'
  }
});

var capas = {
  '🟦 Educación': grupoEducacion,
  '🟨 Remises': grupoRemises,
  '🔥 Calor Educación': heatLayerEducacion,
  '🔥 Calor Remises': heatLayerRemises
};

L.control.layers(null, capas, { collapsed: false }).addTo(map);

var legend = L.control({position: 'bottomright'});
legend.onAdd = function () {
  var div = L.DomUtil.create('div', 'bg-white p-3 rounded shadow text-sm');
  div.innerHTML += '<div><i style="background-image:url(https://cdn-icons-png.flaticon.com/512/167/167707.png);"></i> Educación</div>';
  div.innerHTML += '<div><i style="background-image:url(https://cdn-icons-png.flaticon.com/512/854/854878.png);"></i> Remises</div>';
  return div;
};
legend.addTo(map);
</script>

</body>
</html>
