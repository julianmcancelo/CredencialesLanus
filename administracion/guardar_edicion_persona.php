<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? null;
  $nombre = $_POST['nombre'] ?? '';
  $dni = $_POST['dni'] ?? '';
  $cuit = $_POST['cuit'] ?? '';
  $telefono = $_POST['telefono'] ?? '';
  $email = $_POST['email'] ?? '';

  if ($id && is_numeric($id)) {
    $stmt = $pdo->prepare("UPDATE personas SET nombre = ?, dni = ?, cuit = ?, telefono = ?, email = ? WHERE id = ?");
    $success = $stmt->execute([$nombre, $dni, $cuit, $telefono, $email, $id]);

    if ($success) {
      header("Location: admin.php?mensaje=ok");
      exit;
    } else {
      echo "<p>Error al guardar los cambios. Intentalo de nuevo.</p>";
    }
  } else {
    echo "<p>ID de persona inválido.</p>";
  }
} else {
  echo "<p>Método de acceso inválido.</p>";
}
