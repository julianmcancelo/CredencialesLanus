<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
function renderTablaMobile($habilitaciones, $pdo) {
?>
<div class="flex flex-col gap-4 p-4">
<?php foreach ($habilitaciones as $item): 
    $personas = json_decode($item['personas'] ?? '[]', true);
    $vehiculos = json_decode($item['vehiculos'] ?? '[]', true);
    $destinos = json_decode($item['establecimientos'] ?? '[]', true);
    if (!is_array($personas)) $personas = [];
    if (!is_array($vehiculos)) $vehiculos = [];
    if (!is_array($destinos)) $destinos = [];

    $stmtTurno = $pdo->prepare("SELECT fecha, hora FROM turnos WHERE habilitacion_id = ? ORDER BY id DESC LIMIT 1");
    $stmtTurno->execute([$item['habilitacion_id']]);
    $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);

    $estadoColor = match ($item['estado']) {
        'HABILITADO' => 'bg-green-100 text-green-700',
        'EN TRAMITE' => 'bg-yellow-100 text-yellow-700',
        default => 'bg-red-100 text-red-700'
    };
?>
<div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 flex flex-col gap-2">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-[#891628]"><?= htmlspecialchars($item['nro_licencia']) ?></h2>
            <span class="text-sm font-semibold px-2 py-1 rounded-full <?= $estadoColor ?>">
                <?= htmlspecialchars($item['estado']) ?>
            </span>
        </div>
    </div>

    <?php if ($turno): ?>
    <div class="text-sm text-gray-700">
        📅 Turno: <?= date('d/m/Y', strtotime($turno['fecha'])) ?> a las <?= date('H:i', strtotime($turno['hora'])) ?> hs
    </div>
    <?php else: ?>
    <div class="text-sm text-gray-500 italic">📅 Turno: No asignado</div>
    <?php endif; ?>

    <div class="text-sm text-gray-800 mt-2">
        <?php foreach ($personas as $persona): ?>
            <?php if (strtoupper($persona['rol']) === 'TITULAR'): ?>
                <div>👤 <strong>Titular:</strong> <?= htmlspecialchars($persona['nombre']) ?></div>
            <?php elseif (strtoupper($persona['rol']) === 'CONDUCTOR'): ?>
                <div>🚐 <strong>Chofer:</strong> <?= htmlspecialchars($persona['nombre']) ?></div>
            <?php elseif (strtoupper($persona['rol']) === 'CELADOR'): ?>
                <div>🧍 <strong>Celador:</strong> <?= htmlspecialchars($persona['nombre']) ?></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php foreach ($vehiculos as $vehiculo): ?>
            <div>🚗 <strong>Dominio:</strong> <?= htmlspecialchars($vehiculo['dominio']) ?></div>
        <?php endforeach; ?>

        <?php foreach ($destinos as $dest): ?>
            <div>🏫 <strong>Destino:</strong> <?= htmlspecialchars($dest['nombre']) ?> (<?= ($dest['tipo'] ?? '') === 'remiseria' ? 'Remisería' : 'Instituto' ?>)</div>
        <?php endforeach; ?>
    </div>

    <div class="flex flex-col gap-2 mt-4">
        <a href="ver_habilitacion2.php?id=<?= $item['habilitacion_id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-center">
            🔍 Ver Habilitación
        </a>
        <a href="editar_habilitacion.php?id=<?= $item['habilitacion_id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-center">
            ✏️ Editar
        </a>
        <button onclick="asignarTurnoMobile(<?= $item['habilitacion_id'] ?>, '<?= htmlspecialchars($item['nro_licencia']) ?>')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-center">
            📅 Asignar Turno
        </button>
        <a href="descargar_certificado.php?id=<?= $item['habilitacion_id'] ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-center">
            📄 Descargar PDF
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
// SweetAlert2 para asignar turno mobile
function asignarTurnoMobile(id, licencia) {
  Swal.fire({
    title: 'Asignar turno',
    text: `¿Querés asignar turno para la Licencia ${licencia}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, asignar'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = 'turnos.php?id=' + id;
    }
  });
}
</script>

<?php
}
?>
