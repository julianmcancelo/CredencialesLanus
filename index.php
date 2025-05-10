<?php
function esDispositivoMovil() {
    return preg_match('/android|iphone|ipad|ipod|blackberry|windows phone/i', $_SERVER['HTTP_USER_AGENT']);
}
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'conexion.php';

// Autologin por token
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

// Validación de sesión
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['admin', 'demo', 'usuario'])) {
  echo <<<HTML
  <!DOCTYPE html>
  <html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Restringido</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  </head>
  <body class="flex items-center justify-center min-h-screen bg-gradient-to-br from-red-50 to-red-100">
    <div class="bg-white p-8 rounded-xl shadow-lg text-center border border-red-300">
      <div class="text-red-500 text-5xl mb-4">🔒</div>
      <h1 class="text-2xl font-bold text-red-800 mb-2">Acceso Restringido</h1>
      <p class="text-gray-600 mb-4">Esta sección es solo para usuarios autorizados.</p>
      <a href="login.php" class="text-blue-600 underline font-semibold">Iniciar sesión</a>
      <div class="mt-6 text-gray-500 text-sm">
        ¿Problemas? Contacto:<br>
        📧 <strong>movilidadytransporte@lanus.gob.ar</strong>
      </div>
    </div>
  </body>
  </html>
  HTML;
  exit;
}

$mensaje = null;
$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Usuario';
$es_demo = $_SESSION['rol'] === 'demo';

// Procesar eliminaciones o asociaciones
if (!$es_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['eliminar_persona'])) {
    $pdo->prepare("DELETE FROM habilitaciones_personas WHERE id = ?")->execute([$_POST['eliminar_persona']]);
    $mensaje = "✅ Persona eliminada.";
  } elseif (isset($_POST['eliminar_vehiculo'])) {
    $pdo->prepare("DELETE FROM habilitaciones_vehiculos WHERE id = ?")->execute([$_POST['eliminar_vehiculo']]);
    $mensaje = "✅ Vehículo eliminado.";
  } elseif (isset($_POST['eliminar_establecimiento'])) {
    $pdo->prepare("DELETE FROM habilitaciones_establecimientos WHERE id = ?")->execute([$_POST['eliminar_establecimiento']]);
    $mensaje = "✅ Establecimiento eliminado.";
  } elseif (isset($_POST['habilitacion_id'], $_POST['establecimiento_id'])) {
    $pdo->prepare("INSERT INTO habilitaciones_establecimientos (habilitacion_id, establecimiento_id) VALUES (?, ?)")->execute([
      $_POST['habilitacion_id'], $_POST['establecimiento_id']
    ]);
    $mensaje = "✅ Establecimiento asociado.";
  }
}

// Consultas de habilitaciones
$sqlBase = "
SELECT 
  hg.id AS habilitacion_id, 
  hg.nro_licencia, 
  hg.resolucion,
  hg.estado, 
  hg.vigencia_inicio, 
  hg.vigencia_fin,
  hg.tipo_transporte,
  hg.expte,
  COALESCE((
    SELECT JSON_ARRAYAGG(JSON_OBJECT('id', hp.id, 'nombre', p.nombre, 'rol', hp.rol))
    FROM habilitaciones_personas hp
    JOIN personas p ON p.id = hp.persona_id
    WHERE hp.habilitacion_id = hg.id
  ), JSON_ARRAY()) AS personas,
  COALESCE((
    SELECT JSON_ARRAYAGG(JSON_OBJECT('id', hv.id, 'dominio', v.dominio))
    FROM habilitaciones_vehiculos hv
    JOIN vehiculos v ON v.id = hv.vehiculo_id
    WHERE hv.habilitacion_id = hg.id
  ), JSON_ARRAY()) AS vehiculos,
  COALESCE((
    SELECT JSON_ARRAYAGG(JSON_OBJECT('id', he.id, 'nombre', 
      CASE WHEN he.tipo = 'remiseria' THEN (SELECT nombre FROM remiserias WHERE id = he.establecimiento_id)
           ELSE (SELECT nombre FROM establecimientos WHERE id = he.establecimiento_id)
      END, 'tipo', he.tipo))
    FROM habilitaciones_establecimientos he
    WHERE he.habilitacion_id = hg.id
  ), JSON_ARRAY()) AS establecimientos
FROM habilitaciones_generales hg
WHERE hg.tipo_transporte IN (" . ($es_demo ? "'Demo'" : "'Escolar', 'Remis'") . ")
";

function obtenerHabilitaciones($pdo, $sql, $tipo) {
  $stmt = $pdo->prepare($sql . " AND hg.tipo_transporte = ? ORDER BY hg.id DESC");
  $stmt->execute([$tipo]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$escolares = obtenerHabilitaciones($pdo, $sqlBase, 'Escolar');
$remises   = obtenerHabilitaciones($pdo, $sqlBase, 'Remis');
$demo      = obtenerHabilitaciones($pdo, $sqlBase, 'Demo');
?>



<?php include 'tabla_habilitaciones.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Habilitaciones</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
  .tab-content.hidden { display: none; }
  .tab-content.active { display: block; }

  @media (max-width: 640px) {
    .tab-btn {
      width: 100%;
      text-align: center;
      body {
  overflow-x: hidden;
}

    }
  }
</style>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-[#891628] text-white p-4 shadow">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
      <h1 class="text-xl font-bold">#LaCiudadQueNosMerecemos</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm font-medium">👤 <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="logout.php" class="text-white hover:underline text-sm">Cerrar sesión</a>
        <img src="https://www.lanus.gob.ar/img/logo-footer.svg" class="w-24 sm:w-28" alt="Logo Lanús">
      </div>
    </div>
  </header>
<main class="w-full px-4 sm:px-6 lg:px-8 py-6 overflow-x-clip">
    <div class="bg-white rounded-xl shadow-lg p-6 relative">
      <?php if ($es_demo): ?>
        <div id="modalDemo" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white p-6 rounded-lg max-w-md text-center shadow-lg">
            <h2 class="text-xl font-bold text-[#891628] mb-4">🔒 Cuenta de demostración</h2>
            <p class="text-gray-700 mb-3">Estás usando una cuenta demo. No podés modificar datos reales.</p>
            <p class="text-sm text-gray-600">Para acceso completo, comunicate con:</p>
            <p class="text-sm font-medium text-gray-800 mt-2">Julian Cancelo</p>
            <p class="text-sm text-gray-600">📧 juliancancelo@gmail.com<br>📞 1171631886</p>
            <button onclick="document.getElementById('modalDemo').remove()" class="mt-4 px-4 py-2 bg-[#891628] text-white rounded hover:bg-red-800">Entendido</button>
          </div>
        </div>
      <?php endif; ?>

<!-- Modal de Edición de Habilitación -->
<div id="modalEditarHabilitacion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full p-6 relative">
    <button onclick="cerrarModalEditar()" class="absolute top-2 right-2 text-gray-400 hover:text-red-600 text-2xl font-bold">×</button>
    <h2 class="text-2xl font-bold text-[#891628] mb-4 text-center">✏️ Editar Habilitación</h2>

    <form id="formEditarHabilitacion" method="POST" action="guardar_edicion_habilitacion.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="id" id="edit_id">

      <div>
        <label class="block text-sm font-semibold mb-1">N° de Licencia</label>
        <input type="text" name="nro_licencia" id="edit_nro_licencia" required class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Resolución</label>
        <input type="text" name="resolucion" id="edit_resolucion" class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Tipo</label>
        <select name="tipo" id="edit_tipo" class="w-full border px-3 py-2 rounded shadow-sm">
          <option value="HABILITACION">Habilitación</option>
          <option value="RENOVACION">Renovación</option>
          <option value="CAMBIO MATERIAL">Cambio de Material</option>
          <option value="CAMBIO TITULAR">Cambio de Titular</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Estado</label>
        <select name="estado" id="edit_estado" class="w-full border px-3 py-2 rounded shadow-sm">
          <option value="HABILITADO">HABILITADO</option>
          <option value="NO HABILITADO">NO HABILITADO</option>
          <option value="EN TRAMITE">EN TRÁMITE</option>
          <option value="INICIADO">INICIADO</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Tipo de Transporte</label>
        <select name="tipo_transporte" id="edit_tipo_transporte" class="w-full border px-3 py-2 rounded shadow-sm">
          <option value="Escolar">Escolar</option>
          <option value="Remis">Remis</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Vigencia desde</label>
        <input type="date" name="vigencia_inicio" id="edit_vigencia_inicio" class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Vigencia hasta</label>
        <input type="date" name="vigencia_fin" id="edit_vigencia_fin" class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">N° de Expediente</label>
        <input type="text" name="expte" id="edit_expte" class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-semibold mb-1">Observaciones</label>
        <textarea name="observaciones" id="edit_observaciones" rows="3" class="w-full border px-3 py-2 rounded shadow-sm"></textarea>
      </div>

      <div class="md:col-span-2 flex justify-end gap-2 pt-4">
        <button type="button" onclick="cerrarModalEditar()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
        <button type="submit" class="bg-[#891628] text-white px-6 py-2 rounded hover:bg-red-800 font-semibold">💾 Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <div>
          <h2 class="text-2xl font-bold text-[#891628] mb-1">Panel de Habilitaciones</h2>
          <p class="text-sm text-gray-600">Dirección Gral. de Movilidad y Transporte</p>
       
        </div>
        <?php if ($_SESSION['rol'] !== 'demo'): ?>
        <div class="flex flex-wrap gap-2">
          <a href="registro_persona.php" class="bg-blue-700 hover:bg-blue-900 text-white px-4 py-2 rounded shadow">➕ Persona</a>
          <a href="registro_vehiculo.php" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded shadow">🚐 Vehículo</a>
          <a href="registro_habilitacion.php" class="bg-green-700 hover:bg-green-900 text-white px-4 py-2 rounded shadow">📄 Habilitación</a>
          <a href="registro_establecimiento.php" class="bg-yellow-600 hover:bg-yellow-800 text-white px-4 py-2 rounded shadow">+ 🏫 Establecimiento</a>
                    <a href="turnos.php" class="bg-yellow-600 hover:bg-red-800 text-white px-4 py-2 rounded shadow">📅 Turnos Verificacion</a>
<a href=" mapa.php" class="bg-blue-600 hover:bg-bkye-800 text-white px-4 py-2 rounded shadow">Mapa🗺️</a>
        </div>
        <?php endif; ?>
      </div>
<div class="bg-white rounded-2xl shadow-md p-6 border border-gray-200 transition duration-300">
   <!-- Tabs de tipo de transporte -->
         <?php if (!$es_demo): ?> <div class="flex flex-wrap gap-4 mb-4 justify-center md:justify-start">
        <button onclick="mostrarTab('escolar')" class="tab-btn bg-[#891628] text-white px-4 py-2 rounded">🚸 Transporte Escolar</button>
        <button onclick="mostrarTab('remis')" class="tab-btn bg-gray-800 text-white px-4 py-2 rounded">🚕 Remises</button><?php endif; ?> 
           <?php if ($es_demo): ?> <button onclick="mostrarTab('demo')" class="tab-btn bg-gray-800 text-white px-4 py-2 rounded">Demo</button> <?php endif; ?> 
              <input 
    type="text" 
    id="buscar" 
    placeholder="🔎 Buscar licencia, titular, DNI o dominio..." 
    class="border border-gray-300 rounded-md px-4 py-2 w-80 focus:ring-2 focus:ring-[#891628] focus:outline-none shadow-sm"
  >
      </div>

      <!-- Escolar -->
<div id="escolar" class="tab-content active">
  <?php 
    if (esDispositivoMovil()) {
      require_once 'tabla_habilitaciones_mobile.php';
      renderTablaMobile($escolares, $pdo);
    } else {
      require_once 'tabla_habilitaciones.php';
      renderTabla($escolares, $pdo);
    }
  ?>
</div>

<!-- Remis -->
<div id="remis" class="tab-content hidden">
  <?php 
    if (esDispositivoMovil()) {
      renderTablaMobile($remises, $pdo);
    } else {
      renderTabla($remises, $pdo);
    }
  ?>
</div>

<!-- Demo -->
<div id="demo" class="tab-content hidden">
  <?php 
    if (esDispositivoMovil()) {
      renderTablaMobile($demo, $pdo);
    } else {
      renderTabla($demo, $pdo);
    }
  ?>
</div>
  </main>

  <script>
    function mostrarTab(tabId) {
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
        tab.classList.remove('active');
      });
      const selected = document.getElementById(tabId);
      selected.classList.remove('hidden');
      selected.classList.add('active');
    }
  </script>
  
  <!-- Modal de Asignar Turno -->
<div id="modalTurno" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
<div class="bg-white rounded-lg shadow-lg p-4 sm:p-6 w-[95%] max-w-md relative">
    <button onclick="cerrarModalTurno()" class="absolute top-2 right-2 text-gray-600 hover:text-red-600 text-lg font-bold">×</button>
    <h2 class="text-lg font-bold text-[#891628] mb-4">📅 Asignar Turno - <span id="tituloLicencia"></span></h2>
    <form method="POST" action="asignar_turno.php" class="space-y-4">
      <input type="hidden" name="habilitacion_id" id="inputHabilitacionId">
      <label class="block">
        <span class="font-semibold">Fecha:</span>
        <input type="date" name="fecha" required class="mt-1 border px-3 py-2 rounded w-full" />
      </label>
      <label class="block">
        <span class="font-semibold">Hora:</span>
        <input type="time" name="hora" required class="mt-1 border px-3 py-2 rounded w-full" />
      </label>
      <label class="block">
        <span class="font-semibold">Observaciones:</span>
        <textarea name="observaciones" rows="3" class="mt-1 border px-3 py-2 rounded w-full"></textarea>
      </label>
      <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full">
        ✅ Asignar y Notificar Turno
      </button>
    </form>
  </div>
  
</div>

<script>
  function abrirModalTurno(id, licencia) {
    document.getElementById('inputHabilitacionId').value = id;
    document.getElementById('tituloLicencia').textContent = licencia;
    document.getElementById('modalTurno').classList.remove('hidden');
  }

  function cerrarModalTurno() {
    document.getElementById('modalTurno').classList.add('hidden');
  }
  
  

</script>
<!-- Scripts para abrir y cerrar -->


<script>
document.getElementById('buscar').addEventListener('input', function () {
  const buscar = this.value.toLowerCase();
  const filas = document.querySelectorAll('tbody tr');

  filas.forEach(fila => {
    const texto = fila.innerText.toLowerCase();
    fila.style.display = texto.includes(buscar) ? '' : 'none';
  });
});
</script>

<script>
function abrirModalEditarDesdeBoton(btn) {
  const data = btn.getAttribute('data-habilitacion');
  const habilitacion = JSON.parse(data);
  abrirModalEditar(habilitacion);
}

function abrirModalEditar(habilitacion) {
  document.getElementById('modalEditarHabilitacion').classList.remove('hidden');

  // Completar datos en el modal
  document.getElementById('edit_id').value = habilitacion.habilitacion_id;
  document.getElementById('edit_nro_licencia').value = habilitacion.nro_licencia || '';
  document.getElementById('edit_resolucion').value = habilitacion.resolucion || '';
  document.getElementById('edit_tipo').value = habilitacion.tipo || 'HABILITACION';
  document.getElementById('edit_estado').value = habilitacion.estado || 'HABILITADO';
  document.getElementById('edit_tipo_transporte').value = habilitacion.tipo_transporte || 'Escolar';
  document.getElementById('edit_vigencia_inicio').value = habilitacion.vigencia_inicio || '';
  document.getElementById('edit_vigencia_fin').value = habilitacion.vigencia_fin || '';
  document.getElementById('edit_expte').value = habilitacion.expte || ''; // ← Esto ya lo tenías
  document.getElementById('edit_observaciones').value = habilitacion.observaciones || '';
}

function cerrarModalEditar() {
  document.getElementById('modalEditarHabilitacion').classList.add('hidden');
}
</script>

</body>

<!-- Agregar Animate.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<footer class="bg-[#891628] text-white mt-16">
  <div class="max-w-7xl mx-auto py-8 px-6 flex flex-col md:flex-row justify-between items-center gap-6">

    <!-- Información institucional -->
    <div class="text-center md:text-left space-y-1">
      <p class="font-bold text-lg">Dirección General de Movilidad y Transporte</p>
      <p class="text-sm text-gray-200">Municipalidad de Lanús</p>
      <p class="text-xs text-gray-300 italic">Sistema en funcionamiento desde enero 2025</p>
    </div>

    <!-- Información de contacto -->
    <div class="text-center md:text-right text-sm space-y-1">
      <p>📧 <a href="mailto:movilidadytransporte@lanus.gob.ar" class="underline hover:text-gray-300">movilidadytransporte@lanus.gob.ar</a></p>
      <p>🏢 Av. Hipólito Yrigoyen 3863, Lanús Oeste</p>
      <button onclick="mostrarAcerca()" class="mt-2 underline hover:text-gray-300 text-xs">ℹ️ Acerca del sistema</button>
    </div>

  </div>

  <div class="border-t border-red-400">
    <div class="max-w-7xl mx-auto py-4 px-6 flex flex-col md:flex-row justify-between items-center text-xs text-gray-300 gap-4">
      <p>© <?= date('Y') ?> Municipalidad de Lanús. Todos los derechos reservados.</p>
      <p class="italic">Versión del sistema: 1.4.2 - Última actualización: 26/04/2025</p>
    </div>
  </div>
</footer>

<!-- Modal Acerca -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function mostrarAcerca() {
  Swal.fire({
    title: 'ℹ️ Acerca del Sistema',
    html: `
      <div class="text-left leading-relaxed text-sm space-y-4">
        <h3 class="text-lg font-semibold text-[#891628]">📋 Información General</h3>
        <ul class="list-disc ml-6">
          <li><strong>Nombre:</strong> Sistema de Gestión de Movilidad y Transporte</li>
          <li><strong>Inicio:</strong> Enero 2025</li>
          <li><strong>Versión:</strong> 1.4.2</li>
          <li><strong>Última actualización:</strong> 26 de abril de 2025</li>
        </ul>

        <hr class="border-gray-300">

        <h3 class="text-lg font-semibold text-[#891628]">👨‍💻 Desarrollador</h3>
        <p><strong>Julián Cancelo</strong></p>
        <p>📧 <a href="mailto:juliancancelo@gmail.com" class="underline text-blue-300">juliancancelo@gmail.com</a></p>
        <p>📱 11 7163-1886</p>
        <p>🔗 <a href="https://www.linkedin.com/in/juliancancelo/" target="_blank" class="underline text-blue-300">Perfil de LinkedIn</a></p>

        <hr class="border-gray-300">

        <p class="text-xs text-gray-400 mt-2">
          Este sistema fue desarrollado exclusivamente para optimizar y digitalizar la gestión de movilidad y transporte del Municipio de Lanús.
        </p>
      </div>
    `,
    width: 700,
    padding: '2rem',
    background: '#fff',
    icon: 'info',
    confirmButtonText: 'Cerrar',
    customClass: {
      popup: 'rounded-xl shadow-lg'
    }
  });
}
</script>




</html>
