<?php
require_once 'conexion.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

// Obtener habilitación
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$id]);
$h = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$h) {
    echo json_encode(['success' => false, 'message' => 'Habilitación no encontrada']);
    exit;
}

// Obtener persona (primera asociada)
$stmt = $pdo->prepare("
    SELECT p.nombre AS nombre_completo, p.dni, p.email
    FROM habilitaciones_personas hp
    JOIN personas p ON p.id = hp.persona_id
    WHERE hp.habilitacion_id = ?
    ORDER BY hp.id ASC
    LIMIT 1
");
$stmt->execute([$id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener vehículo
$stmt = $pdo->prepare("
    SELECT v.marca, v.dominio
    FROM habilitaciones_vehiculos hv
    JOIN vehiculos v ON v.id = hv.vehiculo_id
    WHERE hv.habilitacion_id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

function formatearFecha($fecha) {
    if (!$fecha) return '';
    $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
    $ts = strtotime($fecha);
    return date('d', $ts) . ' de ' . $meses[date('m', $ts)] . ' de ' . date('Y', $ts);
}

// Respuesta
echo json_encode([
    'success' => true,
    'data' => [
        'nombre_completo' => $t['nombre_completo'] ?? '-',
        'dni' => $t['dni'] ?? '-',
        'email' => $t['email'] ?? '-',
        'servicio' => 'titular en el Servicio de ' . ucfirst(strtolower($h['tipo_transporte'] ?? '')),
        'marca' => $v['marca'] ?? '-',
        'dominio' => $v['dominio'] ?? '-',
        'expediente' => $h['expte'] ?? '-',
        'resolucion_numero' => $h['resolucion'] ?? '-',
        'fecha_vigencia' => formatearFecha($h['vigencia_inicio'] ?? '')
    ]
]);
