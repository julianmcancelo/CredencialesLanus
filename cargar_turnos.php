<?php
require_once 'conexion.php';

header('Content-Type: application/json');

$eventos = [];

$stmt = $pdo->query("
  SELECT 
    t.id AS turno_id,
    t.fecha,
    t.hora,
    hg.nro_licencia
  FROM turnos t
  JOIN habilitaciones_generales hg ON hg.id = t.habilitacion_id
  ORDER BY t.fecha, t.hora
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $eventos[] = [
    'id' => $row['turno_id'],
    'title' => "🔖 {$row['nro_licencia']}",
    'start' => "{$row['fecha']}T{$row['hora']}",
    'color' => '#891628'
  ];
}

echo json_encode($eventos);
