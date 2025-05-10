<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'conexion.php';

// Login por token_autologin si se pasa por GET
if (!isset($_SESSION['usuario_id']) && isset($_GET['token'])) {
  $stmt = $pdo->prepare("SELECT id, nombre_completo, rol, estado FROM usuarios WHERE token_autologin = ?");
  $stmt->execute([$_GET['token']]);
  $usuario = $stmt->fetch();

  if ($usuario && $usuario['estado'] === 'activo') {
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
    $_SESSION['rol'] = $usuario['rol'];
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Turnos de Verificación Vehicular</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
  <style>
    #calendario {
      min-height: 650px;
      background-color: white;
      padding: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-[#891628] text-white p-4 shadow">
  <div class="max-w-6xl mx-auto flex justify-between items-center">
    <h1 class="text-lg font-bold">Dirección Gral. de Movilidad y Transporte</h1>
    <a href="index.php" class="bg-white text-[#891628] font-semibold px-4 py-1 rounded shadow hover:bg-gray-100">← Volver al Panel</a>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6">
  <h2 class="text-3xl font-bold text-[#891628] mb-6">📅 Turnos de Verificación Vehicular</h2>
  <div id="calendario" class="overflow-x-auto"></div>
</main>

<!-- Modal -->
<div id="modalInfo" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-2xl relative">
    <button onclick="cerrarModal()" class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold text-[#891628] mb-4 text-center">📝 Detalle del Turno</h2>
    <div id="infoContenido" class="text-sm text-gray-800 space-y-3"></div>
    <div class="text-center mt-6">
      <button onclick="cerrarModal()" class="bg-[#891628] hover:bg-red-700 text-white px-6 py-2 rounded-md font-semibold shadow">Cerrar</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

<script>
function cerrarModal() {
  document.getElementById('modalInfo').classList.add('hidden');
}

function abrirModalTurnoCompleto(data) {
  const modal = document.getElementById('modalInfo');
  document.getElementById('infoContenido').innerHTML = `
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
      <div><strong>📋 N° Licencia:</strong> ${data.nro_licencia}</div>
      <div><strong>🚐 Dominio:</strong> ${data.dominio ?? 'N/A'}</div>
      <div><strong>👤 Titular:</strong> ${data.titular ?? 'N/A'}</div>
      <div><strong>📅 Fecha:</strong> ${data.fecha}</div>
      <div><strong>🕗 Hora:</strong> ${data.hora}</div>
      <div><strong>Estado:</strong> ${data.estado}</div>
    </div>

    <div class="flex flex-col sm:flex-row gap-2 mt-6 justify-center">
      <button onclick="anularTurno(${data.turno_id})" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded shadow font-semibold">❌ Anular Turno</button>
      <button onclick="modificarTurno(${data.turno_id})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow font-semibold">✏️ Modificar Turno</button>
      <button onclick="reasignarTurno('${data.nro_licencia}')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow font-semibold">➕ Reasignar Turno</button>
    </div>
  `;
  modal.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendario');

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    },
    events: 'cargar_turnos.php',

    dateClick: function(info) {
      const fecha = info.dateStr;
      const nroLicencia = prompt("📋 Ingresá el N° de licencia (formato 068-xxxx):");

      if (nroLicencia) {
        const hora = prompt("🕗 Ingresá la hora (HH:MM):", "08:00");
        if (!hora) return;

        const formData = new FormData();
        formData.append('nro_licencia', nroLicencia);
        formData.append('fecha', fecha);
        formData.append('hora', hora);

        fetch('asignar_turno_por_licencia.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert("✅ Turno asignado correctamente.");
            calendar.refetchEvents();
          } else {
            alert("⚠️ Error: " + data.error);
          }
        });
      }
    },

    eventClick: function(info) {
      const nroLicencia = info.event.title.replace("🔖 ", "").trim();
      fetch(`buscar_datos_turno.php?nro_licencia=${nroLicencia}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            return;
          }
          abrirModalTurnoCompleto(data);
        });
    }
  });

  calendar.render();
});

// Acciones del modal
function anularTurno(turnoId) {
  if (confirm("¿Seguro que querés anular este turno?")) {
    fetch(`eliminar_turno.php?id=${turnoId}`)
      .then(() => {
        alert("🗑️ Turno anulado correctamente.");
        window.location.reload();
      });
  }
}

function modificarTurno(turnoId) {
  const nuevaFecha = prompt("Nueva fecha (YYYY-MM-DD):");
  const nuevaHora = prompt("Nueva hora (HH:MM):");
  if (nuevaFecha && nuevaHora) {
    const formData = new FormData();
    formData.append('id', turnoId);
    formData.append('fecha', nuevaFecha);
    formData.append('hora', nuevaHora);

    fetch('modificar_turno.php', {
      method: 'POST',
      body: formData
    })
    .then(() => {
      alert("✅ Turno modificado correctamente.");
      window.location.reload();
    });
  }
}

function reasignarTurno(nroLicencia) {
  const fecha = prompt("Nueva fecha (YYYY-MM-DD):");
  const hora = prompt("Nueva hora (HH:MM):");
  if (fecha && hora) {
    const formData = new FormData();
    formData.append('nro_licencia', nroLicencia);
    formData.append('fecha', fecha);
    formData.append('hora', hora);

    fetch('asignar_turno_por_licencia.php', {
      method: 'POST',
      body: formData
    })
    .then(() => {
      alert("✅ Turno reasignado correctamente.");
      window.location.reload();
    });
  }
}
</script>

</body>
</html>
