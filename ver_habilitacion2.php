<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'conexion.php';
session_start();

// Verificar que haya ID válido
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) die("ID inválido.");

// Cargar habilitación
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$id]);
$habilitacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$habilitacion) {
  die("Habilitación no encontrada.");
}

// Cargar personas asociadas
$stmt = $pdo->prepare("
  SELECT p.nombre, p.dni, hp.rol, hp.licencia_categoria
  FROM habilitaciones_personas hp
  JOIN personas p ON p.id = hp.persona_id
  WHERE hp.habilitacion_id = ?
");
$stmt->execute([$id]);
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar vehículo asociado
$stmt = $pdo->prepare("
  SELECT v.dominio, v.marca, v.modelo, v.motor, v.asientos, v.inscripcion_inicial
  FROM habilitaciones_vehiculos hv
  JOIN vehiculos v ON v.id = hv.vehiculo_id
  WHERE hv.habilitacion_id = ?
");
$stmt->execute([$id]);
$vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

// Cargar establecimiento o remisería
$stmt = $pdo->prepare("
  SELECT he.tipo, 
    CASE WHEN he.tipo = 'establecimiento' THEN e.nombre ELSE r.nombre END AS nombre,
    CASE WHEN he.tipo = 'establecimiento' THEN e.domicilio ELSE NULL END AS domicilio
  FROM habilitaciones_establecimientos he
  LEFT JOIN establecimientos e ON (he.tipo = 'establecimiento' AND he.establecimiento_id = e.id)
  LEFT JOIN remiserias r ON (he.tipo = 'remiseria' AND he.establecimiento_id = r.id)
  WHERE he.habilitacion_id = ?
");
$stmt->execute([$id]);
$destino = $stmt->fetch(PDO::FETCH_ASSOC);

// Cargar token de acceso
$stmt = $pdo->prepare("SELECT token FROM tokens_acceso WHERE habilitacion_id = ? ORDER BY creado_en DESC LIMIT 1");
$stmt->execute([$id]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
$token = $tokenData['token'] ?? null;


// Último turno
$stmt = $pdo->prepare("SELECT fecha, hora FROM turnos WHERE habilitacion_id = ? ORDER BY fecha DESC, hora DESC LIMIT 1");
$stmt->execute([$id]);
$ultimoTurno = $stmt->fetch(PDO::FETCH_ASSOC);

// Historial de turnos
$stmt = $pdo->prepare("SELECT fecha, hora FROM turnos WHERE habilitacion_id = ? ORDER BY fecha ASC");
$stmt->execute([$id]);
$historialTurnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Historial de verificaciones por nro_licencia
$stmt = $pdo->prepare("
  SELECT fecha, hora, nombre_titular, dominio, resultado
  FROM verificaciones_historial
  WHERE nro_licencia = ?
  ORDER BY fecha DESC, hora DESC
");
$stmt->execute([$habilitacion['nro_licencia']]);
$historialVerificacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle de Habilitación</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Agregá este CSS para que el calendario se vea bien -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <!-- También el script de flatpickr -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="bg-gray-100 min-h-screen p-4">
<div class="max-w-6xl mx-auto bg-white shadow-lg rounded-xl p-6 space-y-8">

<!-- Encabezado -->
<div class="flex flex-wrap justify-between items-center gap-4">
  <h1 class="text-2xl font-bold text-[#891628]">🔍 Detalle de Habilitación</h1>
  <div class="flex flex-wrap gap-2">
    <a href="index.php" class="text-blue-600 hover:underline">← Volver al Panel</a>
<button onclick="abrirModalGenerarComparecencia(<?= $habilitacion['id'] ?>)" 
  class="inline-flex items-center bg-[#7B1F3A] hover:bg-[#9c2d50] text-white font-bold py-2 px-4 rounded-lg shadow transition">
  📝 Generar Comparecencia
</button>

    <?php if ($token): ?>
      <a href="credencial.php?token=<?= urlencode($token) ?>" target="_blank" 
         class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
        📄 Ver Credencial
      </a>
    <?php endif; ?>
  </div>
</div>

  <!-- Datos Generales -->
  <section>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">📄 Datos Generales</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div><strong>Licencia:</strong> <?= htmlspecialchars($habilitacion['nro_licencia']) ?></div>
      <div><strong>Estado:</strong> <?= htmlspecialchars($habilitacion['estado']) ?></div>
      <div><strong>Vigencia:</strong> Del <?= htmlspecialchars($habilitacion['vigencia_inicio']) ?> al <?= htmlspecialchars($habilitacion['vigencia_fin']) ?></div>
      <div><strong>Tipo de Transporte:</strong> <?= htmlspecialchars($habilitacion['tipo_transporte']) ?></div>
      <div><strong>Tipo de Trámite:</strong> <?= htmlspecialchars($habilitacion['tipo']) ?></div>
      <div><strong>Expediente:</strong> <?= htmlspecialchars($habilitacion['expte']) ?></div>
      <div class="md:col-span-2"><strong>Observaciones:</strong> <?= htmlspecialchars($habilitacion['observaciones']) ?: 'N/A' ?></div>
    </div>
  </section>

  <!-- Personas Asociadas -->
  <section>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">👥 Personas Asociadas</h2>
    <div class="space-y-2 text-sm">
      <?php if ($personas): ?>
        <?php foreach ($personas as $p): ?>
          <div class="border p-2 rounded bg-gray-50">
            <strong><?= htmlspecialchars($p['rol']) ?>:</strong> <?= htmlspecialchars($p['nombre']) ?> (DNI: <?= htmlspecialchars($p['dni']) ?>)
            <?php if ($p['rol'] === 'CONDUCTOR'): ?>
              - Licencia: <?= htmlspecialchars($p['licencia_categoria']) ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-gray-500 italic">Sin personas asociadas.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Vehículo Asociado -->
  <?php if ($vehiculo): ?>
  <section>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">🚐 Vehículo Asociado</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div><strong>Dominio:</strong> <?= htmlspecialchars($vehiculo['dominio']) ?></div>
      <div><strong>Marca:</strong> <?= htmlspecialchars($vehiculo['marca']) ?></div>
      <div><strong>Modelo:</strong> <?= htmlspecialchars($vehiculo['modelo']) ?></div>
      <div><strong>Motor:</strong> <?= htmlspecialchars($vehiculo['motor']) ?></div>
      <div><strong>Asientos:</strong> <?= htmlspecialchars($vehiculo['asientos']) ?></div>
      <div><strong>Inscripción Inicial:</strong> <?= htmlspecialchars($vehiculo['inscripcion_inicial']) ?></div>
    </div>
  </section>
  <?php endif; ?>

<?php if (!empty($destino) && isset($destino['tipo'])): ?>
<section>
  <h2 class="text-xl font-semibold text-gray-800 mb-2"><?= ($destino['tipo'] ?? '') === 'remiseria' ? '🚖 Remisería Asociada' : '🏫 Establecimiento Asociado' ?></h2>
  <div class="text-sm">
    <div><strong>Nombre:</strong> <?= htmlspecialchars($destino['nombre'] ?? 'N/A') ?></div>
    <div><strong>Domicilio:</strong> <?= htmlspecialchars($destino['domicilio'] ?? 'N/A') ?></div>
  </div>
</section>
<?php endif; ?>
  <!-- Último Turno -->
  <section>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">📅 Último Turno</h2>
    <?php if ($ultimoTurno): ?>
      <p class="text-sm">
        <?= date('d/m/Y', strtotime($ultimoTurno['fecha'])) ?> - <?= date('H:i', strtotime($ultimoTurno['hora'])) ?> hs
      </p>
    <?php else: ?>
      <p class="text-gray-500 italic text-sm">Sin turnos asignados.</p>
    <?php endif; ?>
  </section>

  <!-- Historial de Turnos -->
  <section>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">🕘 Historial de Turnos</h2>
    <div class="text-sm space-y-1">
      <?php if ($historialTurnos): ?>
        <?php foreach ($historialTurnos as $t): ?>
          <div class="border-b pb-1"><?= date('d/m/Y', strtotime($t['fecha'])) ?> - <?= date('H:i', strtotime($t['hora'])) ?> hs</div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-gray-500 italic">No hay historial registrado.</p>
      <?php endif; ?>
    </div>
  </section>




  <!-- Historial de Verificaciones -->
  <section>
  <h2 class="text-xl font-semibold text-gray-800 mb-4">🛠️ Historial de Verificación Vehicular</h2>

  <?php if ($historialVerificacion): ?>
    <div class="overflow-x-auto rounded-lg shadow">
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-200 text-gray-700 uppercase text-xs">
          <tr>
            <th class="px-4 py-2">Fecha y Hora</th>
            <th class="px-4 py-2">Titular</th>
            <th class="px-4 py-2">Dominio</th>
            <th class="px-4 py-2">Resultado</th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($historialVerificacion as $v): ?>
  <?php
    $resultado = strtoupper(trim($v['resultado']));
    $bgClass = 'bg-gray-50'; // Default
    $textClass = 'text-gray-800';
    if ($resultado === 'APROBADO') {
      $bgClass = 'bg-green-100';
      $textClass = 'text-green-800';
    } elseif ($resultado === 'RECHAZADO') {
      $bgClass = 'bg-red-100';
      $textClass = 'text-red-800';
    }
  ?>
  <tr class="<?= $bgClass ?> border-b">
    <td class="px-4 py-2"><?= date('d/m/Y', strtotime($v['fecha'])) ?> - <?= date('H:i', strtotime($v['hora'])) ?> hs</td>
    <td class="px-4 py-2"><?= htmlspecialchars($v['nombre_titular']) ?></td>
    <td class="px-4 py-2"><?= htmlspecialchars($v['dominio']) ?></td>
    <td class="px-4 py-2 font-semibold <?= $textClass ?>">
      <?= htmlspecialchars($v['resultado']) ?>
      <?php if ($resultado !== 'APROBADO' && $resultado !== 'RECHAZADO'): ?>
        <button onclick="abrirModalEditarVerificacion('<?= $v['fecha'] ?>', '<?= $v['hora'] ?>', '<?= $habilitacion['nro_licencia'] ?>')" 
          class="ml-2 text-blue-600 hover:text-blue-800 text-xs underline">
          ✏️ Editar
        </button>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>

      </table>
    </div>
  <?php else: ?>
    <p class="text-gray-500 italic text-sm">No hay historial de verificaciones registrado.</p>
  <?php endif; ?>
</section>
<!-- Modal para editar verificación -->
<div id="modalEditarVerificacion" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
    <button onclick="cerrarModalEditarVerificacion()" class="absolute top-2 right-2 text-gray-600 hover:text-red-600 text-2xl">&times;</button>
    <h2 class="text-xl font-bold text-[#891628] mb-4 text-center">✏️ Editar Resultado de Verificación</h2>
<form method="POST" action="editar_verificacion.php" class="space-y-4">
  <input type="hidden" name="fecha" id="editarFecha">
  <input type="hidden" name="hora" id="editarHora">
  <input type="hidden" name="nro_licencia" id="editarLicencia">


      <div>
        <label class="block text-sm font-semibold mb-1">Nuevo Resultado:</label>
        <select name="nuevo_resultado" required class="w-full border rounded px-3 py-2">
          <option value="APROBADO">APROBADO</option>
          <option value="RECHAZADO">RECHAZADO</option>
        </select>
      </div>

      <div class="text-center">
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-semibold">
          ✅ Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModalEditarVerificacion(fecha, hora, licencia) {
  document.getElementById('editarFecha').value = fecha;
  document.getElementById('editarHora').value = hora;
  document.getElementById('editarLicencia').value = licencia;
  
  const modal = document.getElementById('modalEditarVerificacion');
  modal.classList.remove('hidden');
}

function cerrarModalEditarVerificacion() {
  const modal = document.getElementById('modalEditarVerificacion');
  modal.classList.add('hidden');
}
</script>

<!-- MODAL COMPARECENCIA -->
<div id="modalComparecencia" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center">
  <div id="contenidoModalComparecencia" class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-3xl transform scale-90 opacity-0 transition-all duration-300 relative flex flex-col">

    <!-- Botón cerrar -->
    <button onclick="cerrarModalComparecencia()" class="absolute top-4 right-4 text-gray-400 hover:text-red-600 text-3xl font-bold">
      ×
    </button>

    <h2 class="text-3xl font-extrabold text-[#7B1F3A] mb-8 text-center">Generar Comparecencia</h2>

<form id="formComparecencia" method="POST" action="generar_pdf_comparecencia.php" target="_blank" class="flex flex-col gap-6">
  
  <!-- Campo oculto para indicar confirmación -->
  <input type="hidden" name="confirmar" value="1">

  <!-- DATOS -->
  <div id="datosComparecencia" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700 text-base">
    <!-- Los datos dinámicos los completa JS -->
  </div>

  <!-- CAMPOS PARA FECHA, HORA Y TRATAMIENTO -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <div>
      <label class="block font-semibold mb-1">Tratamiento:</label>
      <select name="tratamiento" required class="w-full border rounded p-2">
        <option value="el Señor">Señor</option>
        <option value="la Señora">Señora</option>
      </select>
    </div>

    <div>
      <label class="block font-semibold mb-1">Fecha del Turno:</label>
      <input id="fecha_picker" type="text" name="fecha_solicitud" required class="w-full border rounded p-2" placeholder="YYYY-MM-DD">
    </div>

    <div>
      <label class="block font-semibold mb-1">Hora del Turno:</label>
      <input id="hora_picker" type="text" name="hora_solicitud" required class="w-full border rounded p-2" placeholder="HH:MM">
    </div>
  </div>

  <!-- HIDDEN INPUTS (acá se agregan los campos dinámicamente con JavaScript) -->
  <div id="hiddenInputsComparecencia"></div>

  <!-- BOTONES DE ACCIÓN -->
  <div class="flex justify-center gap-4 mt-8">
    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition">
      ✅ Confirmar y Generar PDF
    </button>
    <button type="button" onclick="enviarCitacion()" class="bg-[#7B1F3A] hover:bg-[#9c2d50] text-white font-bold py-3 px-8 rounded-lg shadow-md transition">
      ✉️ Enviar Citación
    </button>
  </div>

</form>


  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>


let flatpickrFecha = null;
let flatpickrHora = null;

function abrirModalGenerarComparecencia(id) {
  const modal = document.getElementById('modalComparecencia');
  const contenido = document.getElementById('contenidoModalComparecencia');

  modal.classList.remove('hidden');
  contenido.classList.remove('scale-90', 'opacity-0');
  contenido.classList.add('scale-100', 'opacity-100');

  // Limpiar
  document.getElementById('datosComparecencia').innerHTML = '';
  document.getElementById('hiddenInputsComparecencia').innerHTML = '';

  // Traer datos
  fetch('api_obtener_datos_comparecencia.php?id=' + id)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const campos = data.data;
        const datosContainer = document.getElementById('datosComparecencia');
        const hiddenInputs = document.getElementById('hiddenInputsComparecencia');

        for (const [k, v] of Object.entries(campos)) {
          // Mostrar visible
          const div = document.createElement('div');
          div.innerHTML = `<strong>${k.replace(/_/g, ' ')}:</strong> ${v}`;
          datosContainer.appendChild(div);

          // Crear hidden para enviar
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = k;
          input.value = v;
          hiddenInputs.appendChild(input);
        }

        // También confirmar
        const confirmarInput = document.createElement('input');
        confirmarInput.type = 'hidden';
        confirmarInput.name = 'confirmar';
        confirmarInput.value = '1';
        hiddenInputs.appendChild(confirmarInput);
      } else {
        Swal.fire('Error', data.message || 'Error al cargar datos.', 'error');
        cerrarModalComparecencia();
      }
    })
    .catch(err => {
      console.error(err);
      Swal.fire('Error', 'Error al contactar el servidor.', 'error');
      cerrarModalComparecencia();
    });

  // Activar flatpickr
  flatpickr("#fecha_picker", { dateFormat: "Y-m-d" });
  flatpickr("#hora_picker", { enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true });
}




// CERRAR MODAL
function cerrarModalComparecencia() {
  const modal = document.getElementById('modalComparecencia');
  const contenido = document.getElementById('contenidoModalComparecencia');

  modal.classList.add('hidden');
  contenido.classList.remove('scale-100', 'opacity-100');
  contenido.classList.add('scale-90', 'opacity-0');
}

// ENVIAR CITACIÓN
function enviarCitacion() {
  Swal.fire({ title: 'Enviando citación...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

  fetch('enviar_citacion.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        Swal.fire('¡Enviado!', 'La citación fue enviada correctamente.', 'success');
      } else {
        Swal.fire('Error', data.message || 'No se pudo enviar.', 'error');
      }
    })
    .catch(err => {
      console.error(err);
      Swal.fire('Error', 'Error al contactar el servidor.', 'error');
    });
}
</script>



</body>
</html>
