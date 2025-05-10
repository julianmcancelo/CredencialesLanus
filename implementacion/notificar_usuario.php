<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  header('Location: ../login.php');
  exit;
}
require_once '../conexion.php'; // Asegurate que el path sea correcto

// Cambiamos $conn por $pdo
$stmt = $pdo->query("SELECT id, nombre_completo, email FROM usuarios ORDER BY nombre_completo ASC");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Notificar Usuario</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold text-[#891628] mb-4">✉️ Notificar Usuario</h2>

    <form action="enviar_notificacion.php" method="POST" class="space-y-4">
      <div>
        <label for="usuario_id" class="font-semibold">Seleccionar usuario:</label>
        <select name="usuario_id" id="usuario_id" class="w-full p-2 border rounded" required>
          <option value="">-- Seleccionar --</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre_completo']) ?> (<?= $u['email'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="mensaje" class="font-semibold">Mensaje:</label>
        <textarea name="mensaje" id="mensaje" rows="5" class="w-full p-2 border rounded" placeholder="Escribí tu mensaje..." required></textarea>
      </div>

      <button type="submit" class="bg-[#891628] text-white px-4 py-2 rounded hover:bg-[#6e1020]">
        Enviar Notificación
      </button>
    </form>
  </div>
</body>
</html>
