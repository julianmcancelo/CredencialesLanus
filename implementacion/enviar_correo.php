<?php
require_once 'conexion.php';
session_start();

// ✅ Verificar que solo accedan administradores
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
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

function mostrar($mensaje, $tipo = 'info') {
  $clases = [
    'exito' => 'bg-green-100 text-green-800 border-green-300',
    'error' => 'bg-red-100 text-red-800 border-red-300',
    'info' => 'bg-blue-100 text-blue-800 border-blue-300'
  ];
  $clase = $clases[$tipo] ?? $clases['info'];
  echo "<div class='p-4 mb-6 border-l-4 rounded-md $clase'>$mensaje</div>";
}

$id = $_GET['id'] ?? $_POST['id'] ?? '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Enviar Credencial Digital</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4 flex items-center justify-center">
  <div class="bg-white max-w-2xl w-full p-6 rounded-xl shadow-lg border border-gray-200">
    <h1 class="text-2xl font-bold text-[#891628] mb-4 text-center">📩 Envío de Credencial Digital</h1>

    <?php
    if (!is_numeric($id)) {
      mostrar("❌ ID inválido.", 'error');
    } else {
      try {
        $stmt = $pdo->prepare("SELECT p.email, p.nombre, hg.vigencia_fin FROM habilitaciones_personas hp 
          JOIN personas p ON p.id = hp.persona_id 
          JOIN habilitaciones_generales hg ON hg.id = hp.habilitacion_id 
          WHERE hp.habilitacion_id = ? AND hp.rol = 'TITULAR' LIMIT 1");
        $stmt->execute([$id]);
        $titular = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$titular || !filter_var($titular['email'], FILTER_VALIDATE_EMAIL)) {
          mostrar("❌ No se encontró titular con correo válido.", 'error');
        } else {
          $nombre = $titular['nombre'];
          $email = $titular['email'];
          $vigencia = $titular['vigencia_fin'] ?: date('Y-m-d', strtotime('+7 days'));
          $token = bin2hex(random_bytes(32));

          // Guardar token
          $pdo->prepare("INSERT INTO tokens_acceso (habilitacion_id, token, fecha_expiracion) VALUES (?, ?, ?)")
              ->execute([$id, $token, $vigencia]);

          // Enlaces
          $url = "https://credenciales.transportelanus.com.ar/pass.php?token=$token";
          $qr_path = __DIR__ . "/qrs/qr_$id.png";
          $qr_url = "https://credenciales.transportelanus.com.ar/qrs/qr_$id.png";

          if (!file_exists($qr_path)) {
            include_once 'phpqrcode/qrlib.php';
            QRcode::png($url, $qr_path, QR_ECLEVEL_L, 4);
          }

          $asunto = "📄 Tu credencial digital";
          $headers = "MIME-Version: 1.0\r\n";
          $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
          $headers .= "From: Municipalidad de Lanús <no-responder@transportelanus.com.ar>\r\n";

          $mensaje = "
          <html>
          <body style='font-family: \"Segoe UI\", sans-serif; background-color: #f9f9f9; padding: 20px;'>
            <div style='max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
              <div style='text-align: center;'>
                <img src='https://www.lanus.gob.ar/img/logo-footer.svg' alt='Lanús' style='width: 140px; margin-bottom: 20px;'>
              </div>
              <h2 style='color: #891628;'>Hola $nombre,</h2>
              <p>Te enviamos tu credencial digital correspondiente al transporte habilitado registrada en el municipio.</p>
              <p style='margin: 16px 0; text-align: center;'>
                <a href='$url' style='background-color: #891628; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>📄 Ver Credencial</a>
              </p>
              <p style='text-align: center;'>Este enlace estará disponible hasta el día <strong>$vigencia</strong>.</p>
              <div style='text-align: center; margin: 20px 0;'>
                <a href='$url'><img src='$qr_url' alt='QR Credencial' style='width: 140px; border: 1px solid #ccc; padding: 5px; background: #fff; border-radius: 8px;' /></a>
              </div>
              <hr style='margin: 30px 0;'>
              <p style='font-size: 13px; color: #666;'>Podés presentar este correo desde tu celular o imprimir la credencial desde el enlace.</p>
              <div style='text-align: center; margin-top: 30px; font-size: 13px; color: #555;'>
                <p>Dirección General de Movilidad y Transporte</p>
                <p>📞 Teléfono: 4357-5100 (Interno 7137)</p>
                <p>📧 <a href='mailto:movilidadytransporte@lanus.gob.ar' style='color: #891628;'>movilidadytransporte@lanus.gob.ar</a></p>
              </div>
            </div>
          </body>
          </html>";

          if (mail($email, $asunto, $mensaje, $headers)) {
            mostrar("✅ Correo enviado correctamente a <strong>$email</strong>.", 'exito');
          } else {
            mostrar("❌ Falló el envío del correo.", 'error');
          }
        }
      } catch (Exception $e) {
        mostrar("❌ Error: " . htmlspecialchars($e->getMessage()), 'error');
      }
    }
    ?>

    <div class="text-center mt-6">
      <a href="index.php" class="inline-block bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">← Volver al Panel</a>
    </div>
  </div>
</body>
</html>
