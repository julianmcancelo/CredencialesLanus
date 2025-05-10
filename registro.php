<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario inició sesión y tiene rol válido
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  echo "<!DOCTYPE html>
  <html lang='es'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Acceso Restringido</title>
    <link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>
  </head>
  <body class='flex items-center justify-center min-h-screen bg-gradient-to-br from-red-50 to-red-100 px-4'>
    <div class='max-w-lg w-full bg-white border border-red-300 rounded-xl shadow-lg p-8 text-center space-y-4'>
      <div class='text-red-500 text-5xl'>🔒</div>
      <h1 class='text-2xl font-bold text-red-800'>Acceso restringido</h1>
      <p class='text-gray-700'>Esta sección está reservada para usuarios administradores. Por favor, <a href='login.php' class='text-blue-600 underline font-medium'>iniciá sesión</a> con una cuenta autorizada.</p>
      <div class='text-sm text-gray-600 border-t pt-4'>
        <p>Si sos lector invitado, podés visualizar tu credencial digital desde el enlace que te enviamos o solicitá asistencia.</p>
        <p class='mt-1 font-semibold text-red-900'>📧 movilidadytransporte@lanus.gob.ar</p>
      </div>
    </div>
  </body>
  </html>";
  exit;
}
// Procesar formulario de registro
if (isset($_POST['registrarse'])) {
  $nombre = $_POST['nombre'] ?? '';
  $email = $_POST['correo'] ?? '';
  $password = $_POST['clave'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($nombre) || empty($password)) {
    $error = "Datos inválidos.";
  } else {
    $stmtCheck = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
      $error = "Este correo ya está registrado.";
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO admin (nombre, email, password, rol) VALUES (?, ?, ?, 'admin')");
      $stmt->execute([$nombre, $email, $hash]);
      header("Location: index.php");
      exit;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro</title>
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

      <h2 class="text-2xl font-bold text-center text-[#7a003c] mb-4">Registrate</h2>
      <p class="text-center text-sm text-gray-500 mb-6">Completá el formulario para crear tu cuenta.</p>

      <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST">
        <input type="text" name="nombre" placeholder="Tu nombre completo" required class="w-full mb-3 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#c6285d]">
        <input type="email" name="correo" placeholder="Tu correo electrónico" required class="w-full mb-3 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#c6285d]">
        <input type="password" name="clave" placeholder="Contraseña" required class="w-full mb-3 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#c6285d]">
        <button type="submit" name="registrarse" class="w-full bg-[#7a003c] text-white py-2 px-4 rounded-lg hover:bg-[#5a002e] transition">Crear cuenta</button>
      </form>
    </div>
  </div>
</body>
</html>