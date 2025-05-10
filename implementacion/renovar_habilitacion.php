<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';

// Recibir ID desde GET
$id_original = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$id_original]);
$actual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actual) {
  die("Habilitación no encontrada");
}

// Calcular nuevo año
$nuevo_anio = date('Y') + 1;

// Insertar nueva habilitación con datos base
$stmt = $pdo->prepare("INSERT INTO habilitaciones_generales (nro_licencia, estado, vigencia_inicio, vigencia_fin, resolucion, anio) VALUES (?, 'EN TRAMITE', ?, ?, ?, ?)");
$stmt->execute([
  $actual['nro_licencia'],
  $nuevo_anio . '-03-01',
  $nuevo_anio . '-12-31',
  $actual['resolucion'],
  $nuevo_anio
]);

$nueva_id = $pdo->lastInsertId();

// Clonar personas asociadas
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_personas WHERE habilitacion_id = ?");
$stmt->execute([$id_original]);
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($personas as $p) {
  $pdo->prepare("INSERT INTO habilitaciones_personas (habilitacion_id, persona_id, rol, licencia_categoria) VALUES (?, ?, ?, ?)")

      ->execute([$nueva_id, $p['persona_id'], $p['rol'], $p['licencia_categoria']]);
}

// Clonar vehiculo asociado
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_vehiculos WHERE habilitacion_id = ?");
$stmt->execute([$id_original]);
$vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($vehiculos as $v) {
  $pdo->prepare("INSERT INTO habilitaciones_vehiculos (habilitacion_id, vehiculo_id) VALUES (?, ?)")
      ->execute([$nueva_id, $v['vehiculo_id']]);
}

// Clonar establecimiento asociado
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_establecimientos WHERE habilitacion_id = ?");
$stmt->execute([$id_original]);
$establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($establecimientos as $e) {
  $pdo->prepare("INSERT INTO habilitaciones_establecimientos (habilitacion_id, establecimiento_id) VALUES (?, ?)")
      ->execute([$nueva_id, $e['establecimiento_id']]);
}

// Redireccionar al panel o a la edición
header("Location: editar_habilitacion.php?id=$nueva_id");
exit;
