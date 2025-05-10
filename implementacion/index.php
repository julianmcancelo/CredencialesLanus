<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Verificar si el usuario inició sesión y tiene rol válido
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'demo')) {
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
      <p class='text-gray-700'>Esta sección está reservada para usuarios autorizados. Por favor, <a href='login.php' class='text-blue-600 underline font-medium'>iniciá sesión</a> con una cuenta válida.</p>
      <div class='text-sm text-gray-600 border-t pt-4'>
        <p>Si sos lector invitado, podés visualizar tu credencial digital desde el enlace que te enviamos o solicitá asistencia.</p>
        <p class='mt-1 font-semibold text-red-900'>📧 movilidadytransporte@lanus.gob.ar</p>
      </div>
    </div>
  </body>
  </html>";
  exit;
}

require_once 'conexion.php';

$mensaje = null;
$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Usuario';
$es_demo = $_SESSION['rol'] === 'demo';

if (!$es_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['eliminar_persona'])) {
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_personas WHERE id = ?");
    $stmt->execute([$_POST['eliminar_persona']]);
    $mensaje = "✅ Persona eliminada correctamente.";
  } elseif (isset($_POST['eliminar_vehiculo'])) {
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_vehiculos WHERE id = ?");
    $stmt->execute([$_POST['eliminar_vehiculo']]);
    $mensaje = "✅ Vehículo eliminado correctamente.";
  } elseif (isset($_POST['eliminar_establecimiento'])) {
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_establecimientos WHERE id = ?");
    $stmt->execute([$_POST['eliminar_establecimiento']]);
    $mensaje = "✅ Establecimiento eliminado correctamente.";
  } elseif (isset($_POST['habilitacion_id'], $_POST['establecimiento_id'])) {
    $stmt = $pdo->prepare("INSERT INTO habilitaciones_establecimientos (habilitacion_id, establecimiento_id) VALUES (?, ?)");
    $stmt->execute([$_POST['habilitacion_id'], $_POST['establecimiento_id']]);
    $mensaje = "✅ Establecimiento asociado correctamente.";
  }
}

// CONSULTAS
$sqlBase = "
SELECT 
  hg.id AS habilitacion_id, 
  hg.nro_licencia, 
  hg.estado, 
  hg.vigencia_inicio, 
  hg.vigencia_fin,
  hg.tipo_transporte,

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
                                   CASE 
                                     WHEN he.tipo = 'remiseria' 
                                     THEN (SELECT nombre FROM remiserias WHERE id = he.establecimiento_id) 
                                     ELSE (SELECT nombre FROM establecimientos WHERE id = he.establecimiento_id) 
                                   END,
                                   'tipo', he.tipo))
  FROM habilitaciones_establecimientos he
  WHERE he.habilitacion_id = hg.id
), JSON_ARRAY()) AS establecimientos

FROM habilitaciones_generales hg
";

if ($es_demo) {
  $sqlBase .= " WHERE hg.tipo_transporte = 'Demo' ";
} else {
  $sqlBase .= " WHERE hg.tipo_transporte IN ('Escolar', 'Remis') ";
}

$stmtEscolar = $pdo->prepare($sqlBase . " AND hg.tipo_transporte = 'Escolar' ORDER BY hg.id DESC");
$stmtEscolar->execute();
$escolares = $stmtEscolar->fetchAll(PDO::FETCH_ASSOC);

$stmtRemis = $pdo->prepare($sqlBase . " AND hg.tipo_transporte = 'Remis' ORDER BY hg.id DESC");
$stmtRemis->execute();
$remises = $stmtRemis->fetchAll(PDO::FETCH_ASSOC);
$stmtDemo = $pdo->prepare($sqlBase . " AND hg.tipo_transporte = 'Demo' ORDER BY hg.id DESC");
$stmtDemo->execute();
$demo = $stmtDemo->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'tabla_habilitaciones.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Habilitaciones</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .tab-content.hidden { display: none; }
    .tab-content.active { display: block; }
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
 <main class="max-w-7xl mx-auto mt-6 px-4">
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
  <main class="max-w-7xl mx-auto mt-6 px-4">
    <div class="bg-white rounded-xl shadow-lg p-6">
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
          <a href="asociar_establecimiento.php" class="bg-yellow-600 hover:bg-yellow-800 text-white px-4 py-2 rounded shadow">🏫 Establecimiento</a>
        </div>
        <?php endif; ?>
      </div>

      <!-- Tabs de tipo de transporte -->
         <?php if (!$es_demo): ?> <div class="flex flex-wrap gap-4 mb-4 justify-center md:justify-start">
        <button onclick="mostrarTab('escolar')" class="tab-btn bg-[#891628] text-white px-4 py-2 rounded">🚸 Transporte Escolar</button>
        <button onclick="mostrarTab('remis')" class="tab-btn bg-gray-800 text-white px-4 py-2 rounded">🚕 Remises</button><?php endif; ?> 
           <?php if ($es_demo): ?> <button onclick="mostrarTab('demo')" class="tab-btn bg-gray-800 text-white px-4 py-2 rounded">Demo</button> <?php endif; ?> 
      </div>

      <div id="escolar" class="tab-content active">
        <?php renderTabla($escolares, $pdo); ?>
      </div>

      <div id="remis" class="tab-content hidden">
        <?php renderTabla($remises, $pdo); ?>
      </div>
            <div id="demo" class="tab-content hidden">
        <?php renderTabla($demo, $pdo); ?>
      </div>
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
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
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
<div id="modal-edicion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
    <h2 class="text-xl font-bold mb-4 text-[#891628]">Editar Licencia Registrada</h2>
    <form method="POST" action="editar_habilitacion_modal.php">
      <input type="hidden" name="habilitacion_id" id="input-id">

      <label class="block text-sm font-medium text-gray-700 mb-1">Número de Licencia</label>
      <input type="text" name="nro_licencia" id="input-nro-licencia" class="w-full mb-3 border rounded p-2" required>

      <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Transporte</label>
      <input type="text" name="tipo_transporte" id="input-transporte" class="w-full mb-3 border rounded p-2" required>

      <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
      <select name="estado" id="input-estado" class="w-full mb-3 border rounded p-2">
        <option value="HABILITADO">HABILITADO</option>
        <option value="EN TRAMITE">EN TRÁMITE</option>
        <option value="VENCIDO">VENCIDO</option>
      </select>

      <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia Inicio</label>
      <input type="date" name="vigencia_inicio" id="input-vigencia-inicio" class="w-full mb-3 border rounded p-2">

      <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia Fin</label>
      <input type="date" name="vigencia_fin" id="input-vigencia-fin" class="w-full mb-3 border rounded p-2">

      <div class="flex justify-end gap-2">
        <button type="button" onclick="cerrarModalEdicion()" class="px-4 py-2 rounded bg-gray-300">Cancelar</button>
        <button type="submit" class="px-4 py-2 rounded bg-[#891628] text-white font-semibold">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
  function abrirModalEdicion(id, estado, inicio, fin, licencia, transporte) {
    document.getElementById('input-id').value = id;
    document.getElementById('input-estado').value = estado;
    document.getElementById('input-vigencia-inicio').value = inicio;
    document.getElementById('input-vigencia-fin').value = fin;
    document.getElementById('input-nro-licencia').value = licencia;
    document.getElementById('input-transporte').value = transporte;
    document.getElementById('modal-edicion').classList.remove('hidden');
  }

  function cerrarModalEdicion() {
    document.getElementById('modal-edicion').classList.add('hidden');
  }
</script>

</body>
</html>