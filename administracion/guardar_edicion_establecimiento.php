<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? null;
  $nombre = trim($_POST['nombre'] ?? '');
  $domicilio = trim($_POST['domicilio'] ?? '');
  $localidad = trim($_POST['localidad'] ?? '');
  $latitud = trim($_POST['latitud'] ?? '');
  $longitud = trim($_POST['longitud'] ?? '');
  $direccion = trim($_POST['direccion'] ?? '');

  if ($id && $nombre && $domicilio && $localidad && $latitud && $longitud && $direccion) {
    try {
      $stmt = $pdo->prepare("UPDATE establecimientos SET nombre = ?, domicilio = ?, localidad = ?, latitud = ?, longitud = ?, direccion = ? WHERE id = ?");
      $stmt->execute([$nombre, $domicilio, $localidad, $latitud, $longitud, $direccion, $id]);

      header("Location: listado_establecimientos.php?mensaje=✅ Establecimiento actualizado correctamente.");
      exit;
    } catch (PDOException $e) {
      header("Location: listado_establecimientos.php?mensaje=❌ Error: " . urlencode($e->getMessage()));
      exit;
    }
  } else {
    header("Location: listado_establecimientos.php?mensaje=⚠️ Todos los campos son obligatorios.");
    exit;
  }
} else {
  header("Location: listado_establecimientos.php?mensaje=⚠️ Método no permitido.");
  exit;
}
