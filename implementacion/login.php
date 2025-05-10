<?php
session_start();
require_once 'conexion.php';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['correo'] ?? '';
  $password = $_POST['clave'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Correo inválido.";
  } else {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
  $_SESSION['usuario_id'] = $user['id'];
  $_SESSION['nombre_completo'] = $user['nombre']; // ✅ Corrección acá
  $_SESSION['rol'] = $user['rol'];
  header("Location: index.php");
  exit;
} else {
      $error = "Correo o contraseña incorrectos.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de Sesión</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f4f1f1] flex items-center justify-center min-h-screen">
  <div class="flex bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden">
    <div class="hidden md:block md:w-1/2 relative">
      <img src="https://www.lanus.gob.ar/storage/fichas/multimedia/dji-0046-0dcHp.JPG" alt="Imagen lateral" class="h-full w-full object-cover">
      <div class="absolute inset-0 bg-[#7a003c] bg-opacity-40"></div>
    </div>

    <div class="w-full md:w-1/2 p-8">
      <div class="flex justify-center mb-4">
        <div class="bg-[#c6285d] p-2 rounded-full shadow border-4 border-[#7a003c]">
          <img src="https://www.lanus.gob.ar/img/logo-footer.svg" alt="Logo Municipio de Lanús" class="h-16">
        </div>
      </div>

      <h2 class="text-2xl font-bold text-center text-[#7a003c] mb-4">Iniciar Sesión</h2>
      <p class="text-center text-sm text-gray-500 mb-6">Ingresá tu correo y contraseña. Si tenés dificultades, pedí ayuda al personal.</p>

      <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST">
        <div class="mb-4">
          <label for="correo" class="block text-[#7a003c] text-sm font-medium mb-2">Correo electrónico</label>
          <input type="email" id="correo" name="correo" required
                 class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#c6285d]"
                 placeholder="nombre@correo.com">
        </div>

        <div class="mb-6">
          <label for="clave" class="block text-[#7a003c] text-sm font-medium mb-2">Contraseña</label>
          <input type="password" id="clave" name="clave" required
                 class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#c6285d]"
                 placeholder="••••••••">
        </div>

        <div class="mb-4 flex items-center justify-between">
          <div class="flex items-center">
            <input type="checkbox" id="recordarme" name="recordarme" class="text-[#c6285d]">
            <label for="recordarme" class="ml-2 text-gray-700 text-sm">Recordarme</label>
          </div>
          <a href="#" class="text-sm text-[#c6285d] hover:underline">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit"
                class="w-full bg-[#c6285d] text-white py-2 px-4 rounded-lg hover:bg-[#a91f4a] transition duration-300">
          Ingresar
        </button>
      </form>

      <p class="text-center text-gray-700 text-sm mt-4">
        ¿No tenés una cuenta?
        <a href="#" class="text-[#c6285d] hover:underline font-semibold">Registrate</a>
      </p>
    </div>
  </div>
</body>
</html>
