<?php
require 'conexion.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
  http_response_code(400);
  echo json_encode(['error' => 'ID inválido']);
  exit;
}

$stmt = $pdo->prepare("SELECT nro_licencia, tipo_transporte, estado, vigencia_inicio, vigencia_fin FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
  echo json_encode($data);
} else {
  echo json_encode(['error' => 'No se encontró la habilitación']);
}
