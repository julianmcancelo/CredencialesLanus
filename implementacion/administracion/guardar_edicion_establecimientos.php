<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? null;
  $nombre = $_POST['nombre'] ?? '';
  $domicilio = $_POST['domicilio'] ?? '';
  $latitud = $_POST['latitud'] ?? '';
  $longitud = $_POST['longitud'] ?? '';

  if ($id && $nombre && $domicilio && $latitud && $longitud) {
    try {
      $stmt = $pdo->prepare("UPDATE establecimientos SET nombre = ?, domicilio = ?, latitud = ?, longitud = ? WHERE id = ?");
      $stmt->execute([$nombre, $domicilio, $latitud, $longitud, $id]);

      header("Location: listado_establecimientos.php?mensaje=✅ Establecimiento actualizado correctamente.");
      exit;
    } catch (PDOException $e) {
      header("Location: listado_establecimientos.php?mensaje=❌ Error al actualizar: " . urlencode($e->getMessage()));
      exit;
    }
  } else {
    header("Location: listado_establecimientos.php?mensaje=⚠️ Todos los campos son obligatorios.");
    exit;
  }
} else {
  header("Location: listado_establecimientos.php?mensaje=⚠️ Acceso no permitido.");
  exit;
}
