<?php
// Activar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';
require_once 'dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// URLs de imágenes
const HEADER_IMG = 'https://i.ibb.co/Kz8BT4jf/Captura-de-pantalla-2025-04-28-102241.png';
const FOOTER_IMG = 'https://i.ibb.co/hxL09WwB/Captura-de-pantalla-2025-04-28-102250.png';
const MIDDLE_IMG = 'https://i.ibb.co/KxPrKnVp/Captura-de-pantalla-2025-04-28-105218.png';

// Función formatear fecha
function formatearFecha($fecha) {
    if (!$fecha) return '';
    $meses = [
        '01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL',
        '05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO',
        '09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'
    ];
    $ts = strtotime($fecha);
    return date('d', $ts) . ' de ' . $meses[date('m', $ts)] . ' de ' . date('Y', $ts);
}

$modo_previsualizacion = false;
$valores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Confirmar generación PDF
    if (isset($_POST['confirmar']) && $_POST['confirmar'] == '1') {
        $fecha_solicitud = $_POST['fecha_solicitud'] ?? '';
        $hora_solicitud = $_POST['hora_solicitud'] ?? '';
        $tratamiento = $_POST['tratamiento'] ?? 'Señor';

        // Insertar en historial
        $histStmt = $pdo->prepare('
            INSERT INTO oblea_historial (habilitacion_id, fecha_solicitud, hora_solicitud, creado_en) 
            VALUES (?, ?, ?, NOW())
        ');
        $histStmt->execute([
            (int)$_POST['habilitacion_id'], 
            $fecha_solicitud, 
            $hora_solicitud
        ]);

        // Guardar todos los datos en valores
        foreach (['nombre_completo','dni','email','servicio','marca','dominio','expediente','resolucion_numero','fecha_vigencia'] as $campo) {
            $valores[$campo] = $_POST[$campo] ?? '';
        }
        $valores['fecha_solicitud'] = $fecha_solicitud;
        $valores['hora_solicitud'] = $hora_solicitud;
        $valores['tratamiento'] = $tratamiento;
        $valores['habilitacion_id'] = $_POST['habilitacion_id'];

        // Guardar en sesión para la notificación
        $_SESSION['datos_pdf'] = $valores;

        // Construir HTML para PDF
        $html = '<html><body style="font-family:\'Times New Roman\',serif;font-size:11pt;line-height:1.5;margin:0">';
        $html .= '<div style="text-align:center"><img src="'.HEADER_IMG.'" style="width:100%;max-height:150px"></div>';
        $html .= '<div style="padding:30px 50px;margin-top:20px;margin-bottom:80px">';
        $html .= '<p style="text-align:right"><strong>Lanús, ____________________________ </strong></p>';
        $html .= '<p>En el día de la fecha comparece <strong>'.htmlspecialchars($valores['tratamiento']).'</strong> <strong>'.htmlspecialchars($valores['nombre_completo']).'</strong> acreditando su identidad mediante D.N.I N° <strong>'.htmlspecialchars($valores['dni']).'</strong>, en carácter de '.htmlspecialchars($valores['servicio']).', respecto del vehículo marca <strong>'.htmlspecialchars($valores['marca']).'</strong>, dominio <strong>'.htmlspecialchars($valores['dominio']).'</strong>, conforme a lo declarado en el Expediente Electrónico MOS <strong>'.htmlspecialchars($valores['expediente']).'</strong>, autorizado mediante Resolución N° <strong>'.htmlspecialchars($valores['resolucion_numero']).'</strong> de fecha <strong>'.htmlspecialchars($valores['fecha_vigencia']).'</strong>.</p>';
        $html .= '<p>En este acto, y a los fines de finalizar el trámite correspondiente, se procede a la colocación de la oblea de transporte habilitante.</p>';
        $html .= '<br><br>';
        $html .= '<table width="100%" style="margin-top:50px"><tr>';
        $html .= '<td style="text-align:center;vertical-align:top">________________________<br>Firma del interesado/a</td>';
        $html .= '<td style="text-align:center"><img src="'.MIDDLE_IMG.'" style="max-height:200px"></td>';
        $html .= '<td style="text-align:center;vertical-align:top">________________________<br>Firma del inspector actuante</td>';
        $html .= '</tr></table>';
        $html .= '</div>';
        $html .= '<div style="position:fixed;bottom:0;left:0;right:0;text-align:center"><img src="'.FOOTER_IMG.'" style="width:100%;max-height:80px"></div>';
        $html .= '</body></html>';

        // Generar PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();
        $dompdf->stream('comparecencia_'.str_replace(' ','_',$valores['nombre_completo']).'.pdf',['Attachment'=>true]);
        exit;
    }

    // Vista previa
    if (!empty($_POST['habilitacion_id'])) {
        $modo_previsualizacion = true;
        $id = (int)$_POST['habilitacion_id'];

        // Cargar habilitación
        $hStmt = $pdo->prepare('SELECT * FROM habilitaciones_generales WHERE id=?');
        $hStmt->execute([$id]);
        $h = $hStmt->fetch(PDO::FETCH_ASSOC);

        // Cargar titular incluyendo email
        $tStmt = $pdo->prepare('
            SELECT p.nombre, p.dni, p.email
            FROM habilitaciones_personas hp
            JOIN personas p ON p.id=hp.persona_id
            WHERE hp.habilitacion_id=? AND hp.rol="TITULAR"
            LIMIT 1
        ');
        $tStmt->execute([$id]);
        $t = $tStmt->fetch(PDO::FETCH_ASSOC);

        // Cargar vehículo
        $vStmt = $pdo->prepare('
            SELECT v.dominio, v.marca
            FROM habilitaciones_vehiculos hv
            JOIN vehiculos v ON v.id=hv.vehiculo_id
            WHERE hv.habilitacion_id=?
            LIMIT 1
        ');
        $vStmt->execute([$id]);
        $v = $vStmt->fetch(PDO::FETCH_ASSOC);

        // Preparar datos para el formulario
        $valores = [
            'habilitacion_id'   => $id,
            'nombre_completo'   => $t['nombre'] ?? '-',
            'dni'               => $t['dni'] ?? '-',
            'email'             => $t['email'] ?? '',
            'servicio'          => 'titular en el Servicio de '.ucfirst(strtolower($h['tipo_transporte'] ?? '')),
            'marca'             => $v['marca'] ?? '-',
            'dominio'           => $v['dominio'] ?? '-',
            'expediente'        => $h['expte'] ?? '-',
            'resolucion_numero' => $h['resolucion'] ?? '-',
            'fecha_vigencia'    => formatearFecha($h['vigencia_inicio'] ?? '')
        ];
    }
}

// Consulta habilitados
$habilitados = $pdo->query('SELECT id, nro_licencia, tipo_transporte FROM habilitaciones_generales WHERE estado="HABILITADO"')->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Generar Comparecencia PDF</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
    }
    .form-container {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 10px 20px rgba(0,0,0,0.05);
      padding: 2rem;
    }
    .titulo-principal {
      color: #7B1F3A;
      font-weight: 700;
    }
    .btn-principal {
      background-color: #7B1F3A;
      transition: background 0.3s ease;
    }
    .btn-principal:hover {
      background-color: #9c2d50;
    }
    .campo-label {
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
      display: block;
    }
    .campo-input, .campo-select {
      width: 100%;
      padding: 0.75rem;
      border-radius: 0.5rem;
      border: 1px solid #d1d5db;
      background: #f9fafb;
      transition: border-color 0.3s;
    }
    .campo-input:focus, .campo-select:focus {
      border-color: #7B1F3A;
      outline: none;
      background: white;
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center py-8 px-4">
  <div class="w-full max-w-4xl">

    <h1 class="text-4xl titulo-principal text-center mb-10">Generar Comparecencia</h1>

    <?php if (!$modo_previsualizacion): ?>
    <!-- Formulario Selección -->
    <div class="form-container">
      <form method="POST" class="space-y-8">
        <div>
          <label class="campo-label">Seleccione una habilitación:</label>
          <select name="habilitacion_id" required class="campo-select">
            <option value="">-- Seleccione --</option>
            <?php foreach ($habilitados as $h): ?>
              <option value="<?= $h['id'] ?>">
                <?= htmlspecialchars($h['nro_licencia']) ?> — <?= htmlspecialchars($h['tipo_transporte']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="text-center">
          <button type="submit" class="btn-principal text-white font-semibold px-8 py-3 rounded-lg shadow hover:shadow-md">
            Mostrar Vista Previa
          </button>
        </div>
      </form>
    </div>

    <?php else: ?>
    <!-- Formulario Vista previa -->
    <div class="form-container space-y-6">
      <h2 class="text-2xl titulo-principal mb-6 text-center">Vista Previa de Comparecencia</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
        <?php foreach ($valores as $k => $v): ?>
          <div>
            <strong><?= ucwords(str_replace('_', ' ', $k)) ?>:</strong> <?= htmlspecialchars($v) ?>
          </div>
        <?php endforeach; ?>
      </div>

      <hr class="my-6">

      <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="campo-label">Tratamiento:</label>
            <select name="tratamiento" required class="campo-select">
              <option value="el Señor">Señor</option>
              <option value="la Señora">Señora</option>
            </select>
          </div>

          <div>
            <label class="campo-label">Fecha del Turno:</label>
            <input id="fecha_picker" type="text" name="fecha_solicitud" required class="campo-input" placeholder="YYYY-MM-DD">
          </div>

          <div>
            <label class="campo-label">Hora del Turno:</label>
            <input id="hora_picker" type="text" name="hora_solicitud" required class="campo-input" placeholder="HH:MM">
          </div>
        </div>

        <!-- Hidden Inputs -->
        <input type="hidden" name="habilitacion_id" value="<?= $valores['habilitacion_id'] ?>">
        <?php foreach ($valores as $k => $v): ?>
          <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach; ?>

        <div class="text-center mt-8">
          <button type="submit" name="confirmar" value="1" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-8 py-3 rounded-lg shadow hover:shadow-md">
            ✅ Confirmar y Generar PDF
          </button>
          <a href="enviar_citacion.php" target="_blank" class="btn-principal text-white px-8 py-3 rounded-lg shadow">
    ✉️ Enviar Citación al Titular
</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    flatpickr('#fecha_picker', { dateFormat: 'Y-m-d' });
    flatpickr('#hora_picker', { enableTime: true, noCalendar: true, dateFormat: 'H:i', time_24hr: true });
  </script>
</body>
</html>


