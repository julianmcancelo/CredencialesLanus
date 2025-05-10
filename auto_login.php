<?php
session_start();
require_once 'conexion.php';

$token = $_GET['token'] ?? '';
if (!$token) {
  die('Token inválido');
}

$stmt = $pdo->prepare("SELECT id, nombre_completo, rol FROM usuarios WHERE token_autologin = ?");
$stmt->execute([$token]);
$usuario = $stmt->fetch();

if ($usuario) {
  $_SESSION['usuario_id'] = $usuario['id'];
  $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
  $_SESSION['rol'] = $usuario['rol'];

  header("Location: panel_usuario.php");
  exit;
} else {
  echo "Token inválido o expirado.";
}
