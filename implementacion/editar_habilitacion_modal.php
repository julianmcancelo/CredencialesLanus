<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['habilitacion_id'];
  $estado = $_POST['estado'];
  $inicio = $_POST['vigencia_inicio'];
  $fin = $_POST['vigencia_fin'];
  $licencia = $_POST['nro_licencia'];
  $transporte = $_POST['tipo_transporte'];

  $stmt = $pdo->prepare("UPDATE habilitaciones_generales SET estado = ?, vigencia_inicio = ?, vigencia_fin = ?, nro_licencia = ?, tipo_transporte = ? WHERE id = ?");
  $stmt->execute([$estado, $inicio, $fin, $licencia, $transporte, $id]);

  header("Location: index.php"); // o el archivo principal que estés usando
  exit;
}
?>
