
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
function reenviarCorreo($pdo, $id, $token) {
  $stmt = $pdo->prepare("SELECT p.email, p.nombre FROM habilitaciones_personas hp JOIN personas p ON p.id = hp.persona_id WHERE hp.habilitacion_id = ? AND hp.rol = 'TITULAR' LIMIT 1");
  $stmt->execute([$id]);
  $titular = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($titular && filter_var($titular['email'], FILTER_VALIDATE_EMAIL)) {
    return enviarCorreoAlTitular($titular['email'], $titular['nombre'], $token);
  }
  return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['reenviar_credencial'], $_POST['token'])) {
    $id = $_POST['reenviar_credencial'];
    $token = $_POST['token'];

    if (empty($token)) {
      $mensaje = "❌ Falta token.";
    } else {
      if (reenviarCorreo($pdo, $id, $token)) {
        $mensaje = "📩 Correo reenviado correctamente al titular.";
      } else {
        $mensaje = "⚠️ No se pudo reenviar el correo. Verificá que el titular tenga un email válido.";
      }
    }
  }
}

function enviarCorreoAlTitular($email, $nombre, $token) {
  $asunto = "📄 Acceso a tu credencial habilitada";
  $url = "https://credenciales.transportelanus.com.ar/pass.php?token=$token";

  $mensaje = "
    <html>
    <head>
      <meta charset='UTF-8'>
      <title>Acceso a tu credencial</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;'>
      <div style='max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
        <div style='text-align: center;'>
          <img src='https://www.lanus.gob.ar/img/logo-footer.svg' alt='Lanús' style='width: 140px; margin-bottom: 20px;'>
        </div>
        <h2 style='color: #891628;'>Hola $nombre,</h2>
        <p>Te informamos que podés acceder a tu credencial habilitada desde el siguiente botón:</p>
        <div style='text-align: center; margin: 20px 0;'>
          <a href='$url' style='background-color: #891628; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Ver Credencial</a>
        </div>
        <p style='font-size: 14px; color: #555;'>Si tenés alguna consulta, no dudes en comunicarte con nosotros.</p>
        <hr style='margin: 30px 0;'>
        <p style='font-size: 12px; color: #888;'>Dirección Gral. de Movilidad y Transporte<br>Municipalidad de Lanús<br>Correo: movilidadytransporte@lanus.gob.ar</p>
      </div>
    </body>
    </html>
  ";

  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: Movilidad y Transporte <no-reply@lanus.gob.ar>\r\n";

  return mail($email, $asunto, $mensaje, $headers);
}

function renderTabla($habilitaciones, $pdo) {
?>
<div class="w-full overflow-x-auto">
  <table class="table-auto w-full text-sm text-left align-top">
 <thead class="bg-gradient-to-r from-[#fff3f3] to-[#fcebea] text-[#891628] text-xs uppercase">
  <tr>
<th class="hidden">ID</th>    <th class="px-4 py-3 text-left">Licencia</th>
    <th class="px-4 py-3 text-left">Estado</th>
    <th class="px-4 py-3 text-left">Vigencia</th>
    <th class="px-4 py-3 text-left">Turno</th>
    <th class="px-4 py-3 text-left">Titulares</th>
    <th class="px-4 py-3 text-left">Choferes</th>
    <th class="px-4 py-3 text-left">Celadores</th>
    <th class="px-4 py-3 text-left">Vehículos</th>
    <th class="px-4 py-3 text-left">Destino</th>
    <th class="px-4 py-3 text-right">Acciones</th>
  </tr>
</thead>
<tbody class="bg-white divide-y divide-gray-100">
  <?php foreach ($habilitaciones as $item): 
    $personas = json_decode($item['personas'] ?? '[]', true);
    $vehiculos = json_decode($item['vehiculos'] ?? '[]', true);
    $destinos = json_decode($item['establecimientos'] ?? '[]', true);
    if (!is_array($personas)) $personas = [];
    if (!is_array($vehiculos)) $vehiculos = [];
    if (!is_array($destinos)) $destinos = [];

    $esRemis = strtoupper($item['tipo_transporte'] ?? '') === 'REMIS';

    $roles = ['TITULAR' => false, 'CHOFER' => false];
    if (!$esRemis) {
      $roles['CELADOR'] = false;
    }

    foreach ($personas as $p) {
      $rol = strtoupper($p['rol'] ?? '');
      if (isset($roles[$rol])) {
        $roles[$rol] = true;
      }
    }

    $stmtTurno = $pdo->prepare("SELECT fecha, hora FROM turnos WHERE habilitacion_id = ? ORDER BY id DESC LIMIT 1");
    $stmtTurno->execute([$item['habilitacion_id']]);
    $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);

    $stmtToken = $pdo->prepare("SELECT token, fecha_expiracion FROM tokens_acceso WHERE habilitacion_id = ? ORDER BY creado_en DESC LIMIT 1");
    $stmtToken->execute([$item['habilitacion_id']]);
    $tokenInfo = $stmtToken->fetch(PDO::FETCH_ASSOC);
    $token = $tokenInfo['token'] ?? null;
    $tokenVencido = isset($tokenInfo['fecha_expiracion']) && strtotime($tokenInfo['fecha_expiracion']) < time();

    if (!$token) {
      $token = bin2hex(random_bytes(32));
      $expiracion = $item['vigencia_fin'];
      $stmtInsert = $pdo->prepare("INSERT INTO tokens_acceso (habilitacion_id, token, fecha_expiracion) VALUES (?, ?, ?)");
      $stmtInsert->execute([$item['habilitacion_id'], $token, $expiracion]);
      $tokenVencido = strtotime($expiracion) < time();
    }

    $rowClass = $tokenVencido ? 'bg-red-50' : 'hover:bg-gray-50';
  ?>
<td class="px-4 py-3 font-mono font-bold text-[#891628] whitespace-nowrap">
  <?= htmlspecialchars($item['nro_licencia']) ?>
  <button 
    class="ml-2 text-sm text-blue-600 hover:text-blue-800"
    data-habilitacion='<?= htmlspecialchars(json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'
    onclick="abrirModalEditarDesdeBoton(this)"
    title="Editar habilitación">
    ✏️
  </button>
</td>

    <td class="px-4 py-3">
      <span class="px-2 py-0.5 rounded-full font-semibold <?= 
        $item['estado'] === 'HABILITADO' ? 'bg-green-100 text-green-700' : 
        ($item['estado'] === 'EN TRAMITE' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
        <?= $item['estado'] ?>
      </span>
      <?php if ($tokenVencido): ?>
        <span class="ml-2 text-red-600 font-semibold">(Token vencido)</span>
      <?php endif; ?>
    </td>
    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
      Del <?= $item['vigencia_inicio'] ?> al <?= $item['vigencia_fin'] ?>
    </td>
    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
      <?php if ($turno): ?>
        <?= date('d/m/Y', strtotime($turno['fecha'])) ?> - <?= date('H:i', strtotime($turno['hora'])) ?> hs
      <?php else: ?>
        <span class="text-gray-400 italic">Sin asignar</span>
      <?php endif; ?>
    </td>

    <!-- Titulares -->
    <td class="px-4 py-3 text-xs text-gray-800">
      <div class="flex flex-wrap gap-1">
        <?php foreach ($personas as $persona): ?>
          <?php if (strtoupper($persona['rol']) === 'TITULAR'): ?>
            <form method="POST" class="inline">
              <span class="bg-gray-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                <?= htmlspecialchars($persona['nombre']) ?>
                <button type="submit" name="eliminar_persona" value="<?= $persona['id'] ?>" class="text-red-600 hover:text-red-800">🗑️</button>
              </span>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$roles['TITULAR']): ?>
          <a href="cargar_persona.php?id=<?= $item['habilitacion_id'] ?>&rol=titular"
             class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">+ Titular</a>
        <?php endif; ?>
      </div>
    </td>

    <!-- Choferes -->
    <td class="px-4 py-3 text-xs text-gray-800">
      <div class="flex flex-wrap gap-1">
        <?php foreach ($personas as $persona): ?>
          <?php if (strtoupper($persona['rol']) === 'CONDUCTOR'): ?>
            <form method="POST" class="inline">
              <span class="bg-gray-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                <?= htmlspecialchars($persona['nombre']) ?>
                <button type="submit" name="eliminar_persona" value="<?= $persona['id'] ?>" class="text-red-600 hover:text-red-800">🗑️</button>
              </span>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$roles['CHOFER']): ?>
          <a href="cargar_persona.php?id=<?= $item['habilitacion_id'] ?>&rol=chofer"
             class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">+ Chofer</a>
        <?php endif; ?>
      </div>
    </td>

    <!-- Celadores -->
    <?php if (!$esRemis): ?>
    <td class="px-4 py-3 text-xs text-gray-800">
      <div class="flex flex-wrap gap-1">
        <?php foreach ($personas as $persona): ?>
          <?php if (strtoupper($persona['rol']) === 'CELADOR'): ?>
            <form method="POST" class="inline">
              <span class="bg-gray-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                <?= htmlspecialchars($persona['nombre']) ?>
                <button type="submit" name="eliminar_persona" value="<?= $persona['id'] ?>" class="text-red-600 hover:text-red-800">🗑️</button>
              </span>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$roles['CELADOR']): ?>
          <a href="cargar_persona.php?id=<?= $item['habilitacion_id'] ?>&rol=celador"
             class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">+ Celador</a>
        <?php endif; ?>
      </div>
    </td>
    <?php else: ?>
    <td class="px-4 py-3 text-xs text-center text-gray-400 italic">N/A</td>
    <?php endif; ?>

    <!-- Vehículos -->
    <td class="px-4 py-3 text-xs">
      <div class="flex flex-wrap gap-1">
        <?php foreach ($vehiculos as $vehiculo): ?>
          <form method="POST" class="inline">
            <span class="bg-blue-50 text-blue-800 px-2 py-0.5 rounded-full flex items-center gap-1">
              <?= htmlspecialchars($vehiculo['dominio']) ?>
              <button type="submit" name="eliminar_vehiculo" value="<?= $vehiculo['id'] ?>" class="text-red-600 hover:text-red-800">🗑️</button>
            </span>
          </form>
        <?php endforeach; ?>
        <a href="cargar_vehiculo.php?id=<?= $item['habilitacion_id'] ?>" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">+ Vehículo</a>
      </div>
    </td>

    <!-- Destinos -->
    <td class="px-4 py-3 text-xs">
      <div class="flex flex-wrap gap-1">
        <?php foreach ($destinos as $dest): ?>
          <form method="POST" class="inline">
            <span class="<?= ($dest['tipo'] ?? '') === 'remiseria' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' ?> px-2 py-0.5 rounded-full flex items-center gap-1">
              <?= htmlspecialchars($dest['nombre']) ?> 
              <button type="submit" name="eliminar_establecimiento" value="<?= $dest['id'] ?>" class="text-red-600 hover:text-red-800">🗑️</button>
            </span>
          </form>
        <?php endforeach; ?>
        <a href="asociar_establecimiento.php?id=<?= $item['habilitacion_id'] ?>" class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-medium">
          + <?= $esRemis ? 'Remisería' : 'Establecimiento' ?>
        </a>
      </div>
    </td>

    <!-- Acciones -->
  <td class="px-4 py-3 text-sm">
  <div class="flex flex-col gap-1">
    
    <a href="editar_habilitacion.php?id=<?= $item['habilitacion_id'] ?>"
       class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-center shadow transition">
      ✏️ Editar
    </a>

    <a href="enviar_correo.php?id=<?= $item['habilitacion_id'] ?>"
       class="w-full bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-center shadow transition">
      📩 Enviar credencial
    </a>

    <a href="ver_habilitacion2.php?id=<?= $item['habilitacion_id'] ?>" target="_blank"
       class="w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded-md text-center shadow transition">
      🔍 Ver
    </a>

    <button 
      onclick="abrirModalTurno(<?= $item['habilitacion_id'] ?>, '<?= htmlspecialchars($item['nro_licencia']) ?>')"
      class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded-md text-center shadow transition">
      ✍️ Asignar Turno
    </button>

    <a href="descargar_certificado.php?id=<?= $item['habilitacion_id'] ?>" target="_blank"
       class="w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded-md text-center shadow transition">
      📄 PDF Verificación
    </a>

  </div>
</td>
</td>
  </tr>
  <?php endforeach; ?>
</tbody>

    </table>
  </div>
  <!-- Script PDF con descarga automática -->
<script>
  window.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('pdf-content');
    const opt = {
      margin: 0,
      filename: 'certificado_verificacion_vehicular.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save().then(() => {
      setTimeout(() => {
        window.close(); // Intenta cerrar la ventana después de generar el PDF
      }, 2000);
    });
  });
  

</script>


<?php


}
