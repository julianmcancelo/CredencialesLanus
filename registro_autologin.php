<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';

function enviarCorreo($para, $asunto, $mensajeHTML) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Transporte Lanús <no-reply@transportelanus.com.ar>\r\n";
    return mail($para, $asunto, $mensajeHTML, $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = $_POST['nombre_completo'] ?? '';
    $email = $_POST['email'] ?? '';
    $legajo = $_POST['legajo'] ?? '';
    $hwid = $_POST['hwid'] ?? null;

    if (!$nombre_completo || !$email || !$legajo) {
        die("Faltan campos obligatorios.");
    }

    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, legajo, hwid, token_autologin, rol, estado) VALUES (?, ?, ?, ?, ?, 'usuario', 'activo')");
    $stmt->execute([$nombre_completo, $email, $legajo, $hwid, $token]);

    $enlace = "https://credenciales.transportelanus.com.ar/index.php?token=$token";
    $asunto = "Tu acceso al sistema de Transporte Lanús";
    $mensaje = "<p>Hola <strong>$nombre_completo</strong>,</p>
    <p>Tu acceso al sistema ha sido creado correctamente. Ingresá desde este enlace sin necesidad de iniciar sesión:</p>
    <div style='padding: 10px; background: #f3f4f6; border-left: 4px solid #059669; font-size: 14px;'>
      <a href='$enlace'>$enlace</a>
    </div>
    <p>Este enlace es único y seguro.</p>
    <p style='margin-top: 20px;'>Dirección General de Movilidad y Transporte - Municipio de Lanús</p>";

    enviarCorreo($email, $asunto, $mensaje);

    echo "<div class='min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 to-white px-4'>
    <div class='bg-white max-w-md w-full rounded-xl shadow-lg p-6 border border-green-200'>
        <div class='text-center'>
            <div class='text-green-600 text-4xl mb-2'>✅</div>
            <h2 class='text-xl font-bold text-green-800 mb-1'>Registro exitoso</h2>
            <p class='text-sm text-gray-700 mb-2'>El acceso fue enviado a <strong>$email</strong>.</p>
            <p class='text-sm text-gray-500 mb-4'>Guardá este enlace para futuras consultas:</p>
            <div class='bg-gray-50 p-4 rounded border text-sm text-gray-800 break-all mb-4'>
                <a href='$enlace' target='_blank' class='text-blue-600 hover:underline'>$enlace</a>
            </div>
            <a href='$enlace' target='_blank' class='inline-block bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 transition'>Ir al sistema</a>
        </div>
    </div>
    </div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Usuario</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-white min-h-screen flex items-center justify-center px-4">
  <form method="POST" class="bg-white p-8 rounded-xl shadow-xl w-full max-w-lg border border-gray-200">
    <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">📝 Registro de Usuario</h1>
    <p class="text-sm text-gray-600 text-center mb-6">Completá el formulario con tus datos para recibir acceso automático al sistema de credenciales de transporte.</p>

    <div class="mb-4">
      <label class="block text-gray-700 font-semibold mb-1">Nombre completo</label>
      <input type="text" name="nombre_completo" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
    </div>

    <div class="mb-4">
      <label class="block text-gray-700 font-semibold mb-1">Correo electrónico</label>
      <input type="email" name="email" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
    </div>

    <div class="mb-4">
      <label class="block text-gray-700 font-semibold mb-1">Legajo</label>
      <input type="text" name="legajo" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
    </div>

    <div class="mb-6">
      <label class="block text-gray-700 font-semibold mb-1">HWID <span class="text-sm text-gray-500">(opcional)</span></label>
      <input type="text" name="hwid" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
    </div>

    <button type="submit" class="w-full bg-green-600 text-white font-semibold px-5 py-2 rounded hover:bg-green-700 transition">Registrar y enviar acceso</button>
  </form>
</body>
</html>
