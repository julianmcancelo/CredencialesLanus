<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? null;
  $dominio = $_POST['dominio'] ?? '';
  $marca = $_POST['marca'] ?? '';
  $modelo = $_POST['modelo'] ?? '';
  $motor = $_POST['motor'] ?? '';
  $asientos = $_POST['asientos'] ?? null;
  $inscripcion = $_POST['inscripcion_inicial'] ?? '';

  if ($id && $dominio && $marca && $modelo && $motor && $asientos && $inscripcion) {
    try {
      $stmt = $pdo->prepare("UPDATE vehiculos SET dominio = ?, marca = ?, modelo = ?, motor = ?, asientos = ?, inscripcion_inicial = ? WHERE id = ?");
      $stmt->execute([$dominio, $marca, $modelo, $motor, $asientos, $inscripcion, $id]);

      header("Location: listado_vehiculos.php?mensaje=✅ Vehículo actualizado correctamente.");
      exit;
    } catch (PDOException $e) {
      header("Location: listado_vehiculos.php?mensaje=❌ Error al actualizar: " . urlencode($e->getMessage()));
      exit;
    }
  } else {
    header("Location: listado_vehiculos.php?mensaje=⚠️ Todos los campos son obligatorios.");
    exit;
  }
} else {
  header("Location: listado_vehiculos.php?mensaje=⚠️ Método no permitido.");
  exit;
}
