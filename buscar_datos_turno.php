<?php
require_once 'conexion.php';

$nro_licencia = $_GET['nro_licencia'] ?? '';

if (!$nro_licencia) {
  echo json_encode(['error' => 'N° de licencia inválido.']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT t.id AS turno_id, t.fecha, t.hora, hg.id AS habilitacion_id, hg.estado,
         hg.nro_licencia,
         (SELECT v.dominio FROM habilitaciones_vehiculos hv JOIN vehiculos v ON v.id = hv.vehiculo_id WHERE hv.habilitacion_id = hg.id LIMIT 1) AS dominio,
         (SELECT p.nombre FROM habilitaciones_personas hp JOIN personas p ON p.id = hp.persona_id WHERE hp.habilitacion_id = hg.id AND hp.rol = 'TITULAR' LIMIT 1) AS titular
  FROM turnos t
  JOIN habilitaciones_generales hg ON t.habilitacion_id = hg.id
  WHERE hg.nro_licencia = ?
  ORDER BY t.fecha DESC, t.hora DESC
  LIMIT 1
");
$stmt->execute([$nro_licencia]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
  echo json_encode($data);
} else {
  echo json_encode(['error' => 'No se encontró turno para esa licencia.']);
}
?>
