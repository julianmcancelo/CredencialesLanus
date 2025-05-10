<?php
require_once 'conexion.php';
session_start();

// Solo admins pueden anular turnos
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  http_response_code(403);
  echo json_encode(["error" => "Acceso no autorizado."]);
  exit;
}

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  http_response_code(400);
  echo json_encode(["error" => "ID de turno inválido."]);
  exit;
}

try {
  $stmt = $pdo->prepare("DELETE FROM turnos WHERE id = ?");
  $stmt->execute([$id]);

  echo json_encode(["success" => "Turno anulado correctamente."]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["error" => "Error al anular el turno."]);
}
