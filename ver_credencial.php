<?php
require_once 'conexion.php';

$token = $_GET['token'] ?? '';

if (!$token || strlen($token) < 64) {
  die("⚠️ Token inválido.");
}

try {
  $stmt = $pdo->prepare("
    SELECT habilitacion_id 
    FROM tokens_acceso 
    WHERE token = ? 
      AND fecha_expiracion >= NOW()
    LIMIT 1
  ");
  $stmt->execute([$token]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    die("❌ Este enlace ha expirado o no es válido.");
  }

  $id = $row['habilitacion_id'];

  // Redireccionar a la credencial real
  header("Location: credencial.php?id=$id");
  exit;

} catch (PDOException $e) {
  echo "❌ Error en la base de datos: " . $e->getMessage();
}
