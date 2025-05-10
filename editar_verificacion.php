<?php
session_start();
require_once 'conexion.php';

// Verificar sesión admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  header('Location: login.php');
  exit;
}

// Recibir datos
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$nro_licencia = $_POST['nro_licencia'] ?? '';
$nuevo_resultado = $_POST['nuevo_resultado'] ?? '';

if (!$fecha || !$hora || !$nro_licencia || !$nuevo_resultado) {
  $_SESSION['mensaje_error'] = "Faltan datos para actualizar.";
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit;
}

// Actualizar
try {
  $stmt = $pdo->prepare("UPDATE verificaciones_historial SET resultado = ? WHERE fecha = ? AND hora = ? AND nro_licencia = ?");
  $stmt->execute([$nuevo_resultado, $fecha, $hora, $nro_licencia]);

  $_SESSION['mensaje_exito'] = "Resultado actualizado correctamente.";
} catch (PDOException $e) {
  $_SESSION['mensaje_error'] = "Error al actualizar: " . $e->getMessage();
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>
