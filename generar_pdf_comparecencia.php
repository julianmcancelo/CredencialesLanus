<?php
session_start();
require_once 'conexion.php';
require_once 'dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();

const HEADER_IMG = 'https://i.ibb.co/Kz8BT4jf/Captura-de-pantalla-2025-04-28-102241.png';
const FOOTER_IMG = 'https://i.ibb.co/hxL09WwB/Captura-de-pantalla-2025-04-28-102250.png';
const MIDDLE_IMG = 'https://i.ibb.co/KxPrKnVp/Captura-de-pantalla-2025-04-28-105218.png';

function formatearFecha($fecha) {
    if (!$fecha) return '';
    $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
    $ts = strtotime($fecha);
    return date('d', $ts) . ' de ' . $meses[date('m', $ts)] . ' de ' . date('Y', $ts);
}

$modo_previsualizacion = false;
$valores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] == '1') {
        $campos = ['nombre_completo', 'dni', 'servicio', 'marca', 'dominio', 'resolucion_numero', 'fecha_vigencia', 'tratamiento', 'expediente'];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo])) $_POST[$campo] = '';
        }

        $valores = $_POST;

        $html = '<html><body style="font-family:Times New Roman,serif;font-size:11pt;line-height:1.5;margin:0">';
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

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        ob_end_clean();
        $dompdf->stream('comparecencia_'.str_replace(' ', '_', $valores['nombre_completo']).'.pdf', ['Attachment' => true]);
        exit;
    }

    // Previsualización
    if (!empty($_POST['habilitacion_id'])) {
        $modo_previsualizacion = true;
        $id = (int)$_POST['habilitacion_id'];

        $hStmt = $pdo->prepare('SELECT * FROM habilitaciones_generales WHERE id = ?');
        $hStmt->execute([$id]);
        $h = $hStmt->fetch(PDO::FETCH_ASSOC);

        // Traer cualquier persona asociada (sin filtro de rol)
$tStmt = $pdo->prepare('
    SELECT p.nombre AS nombre_completo, p.dni, p.email
    FROM habilitaciones_personas hp
    LEFT JOIN personas p ON hp.persona_id = p.id
    WHERE hp.habilitacion_id = ?
    ORDER BY hp.id ASC
    LIMIT 1
');
$tStmt->execute([$id]);
$t = $tStmt->fetch(PDO::FETCH_ASSOC);

        // Vehículo
        $vStmt = $pdo->prepare('
            SELECT v.dominio, v.marca
            FROM habilitaciones_vehiculos hv
            JOIN vehiculos v ON v.id = hv.vehiculo_id
            WHERE hv.habilitacion_id = ?
            LIMIT 1
        ');
        $vStmt->execute([$id]);
        $v = $vStmt->fetch(PDO::FETCH_ASSOC);

        $valores = [
            'habilitacion_id'   => $id,
            'nombre_completo'   => $t['nombre_completo'] ?? '',
            'dni'               => $t['dni'] ?? '',
            'email'             => $t['email'] ?? '',
            'servicio'          => 'titular en el Servicio de '.ucfirst(strtolower($h['tipo_transporte'] ?? '')),
            'marca'             => $v['marca'] ?? '',
            'dominio'           => $v['dominio'] ?? '',
            'expediente'        => $h['expte'] ?? '',
            'resolucion_numero' => $h['resolucion'] ?? '',
            'fecha_vigencia'    => formatearFecha($h['vigencia_inicio'] ?? ''),
            'tratamiento'       => 'El/la Sr./Sra.'
        ];
    }
}
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Generar Comparecencia</title>
  <style>
    body { font-family: sans-serif; padding: 2rem; }
    label, select, button { display: block; margin-bottom: 1rem; }
  </style>
</head>
<body>

<h2>📄 Generar constancia de comparecencia</h2>

<form method="post">
  <label>Seleccionar habilitación:</label>
  <select name="habilitacion_id">
    <option value="">-- Seleccionar --</option>
    <?php
    $habilitados = $pdo->query('SELECT id, nro_licencia, tipo_transporte FROM habilitaciones_generales WHERE estado = "HABILITADO"')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($habilitados as $hab):
    ?>
      <option value="<?= $hab['id'] ?>"><?= htmlspecialchars($hab['nro_licencia']) ?> - <?= htmlspecialchars($hab['tipo_transporte']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">🟢 Cargar datos</button>
</form>

<?php if ($modo_previsualizacion): ?>
  <hr>
  <h3>✅ Previsualizar comparecencia</h3>
  <form method="post">
    <input type="hidden" name="confirmar" value="1">
    <?php foreach ($valores as $k => $v): ?>
      <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
    <?php endforeach; ?>
    <ul>
      <li><strong>Nombre:</strong> <?= $valores['nombre_completo'] ?></li>
      <li><strong>DNI:</strong> <?= $valores['dni'] ?></li>
      <li><strong>Servicio:</strong> <?= $valores['servicio'] ?></li>
      <li><strong>Marca:</strong> <?= $valores['marca'] ?></li>
      <li><strong>Dominio:</strong> <?= $valores['dominio'] ?></li>
      <li><strong>Expediente:</strong> <?= $valores['expediente'] ?></li>
      <li><strong>Resolución:</strong> <?= $valores['resolucion_numero'] ?></li>
      <li><strong>Vigencia desde:</strong> <?= $valores['fecha_vigencia'] ?></li>
    </ul>
    <button type="submit">📄 Generar PDF</button>
  </form>
<?php endif; ?>

</body>
</html>
