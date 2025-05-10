<?php
require_once 'conexion.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  die("ID inválido.");
}

$stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) die("Habilitación no encontrada.");

$stmt = $pdo->prepare("SELECT p.nombre, p.dni, p.domicilio FROM habilitaciones_personas hp JOIN personas p ON p.id = hp.persona_id WHERE hp.habilitacion_id = ? AND hp.rol = 'TITULAR' LIMIT 1");
$stmt->execute([$id]);
$titular = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT p.nombre, p.dni FROM habilitaciones_personas hp JOIN personas p ON p.id = hp.persona_id WHERE hp.habilitacion_id = ? AND hp.rol = 'CONDUCTOR' LIMIT 1");
$stmt->execute([$id]);
$chofer = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT v.* FROM habilitaciones_vehiculos hv JOIN vehiculos v ON v.id = hv.vehiculo_id WHERE hv.habilitacion_id = ? LIMIT 1");
$stmt->execute([$id]);
$vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

$fechaHoy = date('d/m/Y');
$horaHoy = date('H:i');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Descargando PDF...</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    @media print {
      html, body {
        width: 210mm;
        height: 297mm;
        overflow: hidden;
        background: white;
      }
      table, th, td {
        border: 1px solid #891628 !important;
        table-layout: fixed !important;
        width: 100% !important;
        word-wrap: break-word;
      }
      tr {
        page-break-inside: avoid !important;
      }
    }

    body {
      font-family: 'Segoe UI', sans-serif;
    }

    .header-bg {
      background: #891628;
    }

    .logo-white {
      filter: brightness(0) invert(1);
    }
  </style>
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">

<div id="pdf-content" class="bg-white border shadow-xl w-[210mm] min-h-[297mm] text-[13px] leading-snug tracking-tight">

  <!-- Encabezado -->
  <div class="header-bg text-white px-6 py-4 flex justify-between items-center">
    <div>
      <img src="https://www.lanus.gob.ar/img/logo-footer.svg" class="w-40 logo-white mb-2">
      <p class="text-xs">Subsecretaría de Ordenamiento Urbano<br>Dirección Gral. de Movilidad y Transporte</p>
    </div>
    <div class="text-right">
      <h1 class="text-xl font-bold uppercase">CERTIFICADO DE VERIFICACIÓN VEHICULAR</h1>
      <p class="text-sm mt-2">Fecha: <?= $fechaHoy ?><br>Hora: <?= $horaHoy ?></p>
    </div>
  </div>

  <!-- Datos generales -->
  <div class="grid grid-cols-2 px-6 py-3 bg-gray-50 border-b text-sm">
    <div><strong>Expediente Nº:</strong> <?= $data['expte'] ?? '---' ?></div>
    <div><strong>Licencia Nº:</strong> <?= $data['nro_licencia'] ?? '---' ?></div>
    <div><strong>Tipo de Habilitación:</strong> <?= $data['tipo'] ?? '---' ?></div>
    <div><strong>Transporte:</strong> <?= $data['tipo_transporte'] ?? '---' ?></div>
  </div>

  <!-- Datos organizados -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-6 py-4 text-sm">
    <div>
      <h3 class="text-[#891628] font-bold mb-2 border-b pb-1">Titular</h3>
      <p><strong>Nombre:</strong> <?= $titular['nombre'] ?? '---' ?></p>
      <p><strong>DNI:</strong> <?= $titular['dni'] ?? '---' ?></p>
      <p><strong>Domicilio:</strong> <?= $titular['domicilio'] ?? '---' ?></p>
    </div>
    <div>
      <h3 class="text-[#891628] font-bold mb-2 border-b pb-1">Conductor</h3>
      <p><strong>Nombre:</strong> <?= $chofer['nombre'] ?? '---' ?></p>
      <p><strong>DNI:</strong> <?= $chofer['dni'] ?? '---' ?></p>
    </div>
    <div>
      <h3 class="text-[#891628] font-bold mb-2 border-b pb-1">Vehículo</h3>
      <p><strong>Dominio:</strong> <?= $vehiculo['dominio'] ?? '---' ?></p>
      <p><strong>Marca:</strong> <?= $vehiculo['marca'] ?? '---' ?></p>
      <p><strong>Modelo:</strong> <?= $vehiculo['modelo'] ?? '---' ?></p>
      <p><strong>Motor:</strong> <?= $vehiculo['motor'] ?? '---' ?></p>
      <p><strong>Asientos:</strong> <?= $vehiculo['asientos'] ?? '---' ?></p>
      <p><strong>Inscripción Inicial:</strong> <?= $vehiculo['inscripcion_inicial'] ?? '---' ?></p>
    </div>
    <div>
      <h3 class="text-[#891628] font-bold mb-2 border-b pb-1">Agencia / Observaciones</h3>
      <p><strong>Agencia:</strong> <?= $data['agencia'] ?? '---' ?></p>
      <p><strong>Observaciones:</strong><br><?= $data['observaciones'] ?? '---' ?></p>
    </div>
  </div>

  <!-- Tabla -->
  <hr class="mx-6 border-t-2 border-[#891628] my-5">
  <div class="px-6">
    <h3 class="text-center text-[#891628] font-bold mb-3 text-[14px] border-b-2 border-[#891628] pb-1">
      DETALLES Y OBSERVACIONES DEL VEHÍCULO
    </h3>
    <table class="w-full border-collapse border border-[#891628] text-[12px]">
      <thead class="bg-gray-100">
        <tr class="text-left">
          <th class="border border-[#891628] px-2 py-2 w-[38%]">Descripción</th>
          <th class="border border-[#891628] px-2 py-2 text-center w-[10%]">Bien</th>
          <th class="border border-[#891628] px-2 py-2 text-center w-[10%]">Regular</th>
          <th class="border border-[#891628] px-2 py-2 text-center w-[10%]">Mal</th>
          <th class="border border-[#891628] px-2 py-2 w-[32%]">Observaciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $items = [
          "Estado general de carrocería y vidrios",
          "Espejos retrovisores (Der./Izq.)",
          "Luces (posición, freno, guiños)",
          "Cubiertas y profundidad",
          "Interior: tapizados y cinturones",
          "Apoyacabezas",
          "Cinturones funcionales",
          "Matafuego vigente",
          "Kit de emergencias completo",
          "Mampara divisoria (si aplica)"
        ];
        foreach ($items as $desc): ?>
        <tr class="h-10 align-top">
          <td class="border border-[#891628] px-2"><?= $desc ?></td>
          <td class="border border-[#891628]"></td>
          <td class="border border-[#891628]"></td>
          <td class="border border-[#891628]"></td>
          <td class="border border-[#891628]"></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Firmas -->
  <hr class="mx-6 border-t-2 border-[#891628] my-6">
  <div class="grid grid-cols-2 px-6 mt-4 gap-6 text-[13px]">
    <div class="text-center pt-4">
      <p class="border-t border-[#444] pt-2">Firma del Interesado</p>
    </div>
    <div class="text-center pt-4">
      <p class="border-t border-[#444] pt-2">Firma del Agente Verificador</p>
    </div>
  </div>
</div>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('pdf-content');
    const opt = {
      margin: 0,
      filename: 'expte-<?= preg_replace("/[^a-zA-Z0-9\-]/", "_", $data['expte']) ?>.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save().then(() => {
      setTimeout(() => window.close(), 2000);
    });
  });
</script>

</body>
</html>
