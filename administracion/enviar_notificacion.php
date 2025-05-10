<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ids = $_POST['persona_ids'] ?? [];
  $mensaje = trim($_POST['mensaje'] ?? '');

  if (empty($ids) || $mensaje === '') {
    die("<div class='min-h-screen flex items-center justify-center bg-red-50 text-center text-red-700 font-semibold text-lg'>❌ Debés seleccionar al menos una persona y escribir un mensaje.</div>");
  }

  $in = str_repeat('?,', count($ids) - 1) . '?';
  $stmt = $pdo->prepare("SELECT nombre, email FROM personas WHERE id IN ($in)");
  $stmt->execute($ids);
  $destinatarios = $stmt->fetchAll();

  $asunto = "📬 Notificación del sistema";
  $cabeceras = "From: Transporte Público Lanús <transportepublicolanus@gmail.com>\r\n";
  $cabeceras .= "MIME-Version: 1.0\r\n";
  $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
$cabeceras .= "Bcc: transportepublicolanus@gmail.com\r\n";

  $logo_url = "https://www.lanus.gob.ar/img/logo-footer.svg";

  echo "<div class='min-h-screen bg-gray-50 py-10 px-4'>
          <div class='max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-8 border border-gray-200'>
            <h1 class='text-4xl font-bold text-[#891628] mb-8 text-center'>Vista previa de las notificaciones</h1>
            <div class='space-y-10'>";

  foreach ($destinatarios as $persona) {
    $nombre = htmlspecialchars($persona['nombre']);
    $email = htmlspecialchars($persona['email']);
    $mensajeHTML = nl2br(htmlspecialchars($mensaje));

    $cuerpo = '
      <div style="background: #fffdfc; padding: 20px; font-family: \'Segoe UI\', sans-serif;">
        <div style="max-width: 600px; margin: auto; border-radius: 1rem; background: #ffffff; border: 1px solid #e0e0e0; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
          <div style="background-color: #891628; color: white; padding: 20px; text-align: center;">
            <img src="' . $logo_url . '" alt="Lanús" style="height: 40px; margin-bottom: 10px; filter: brightness(0.15) sepia(1) hue-rotate(330deg) saturate(8) contrast(1.2);" />
            <h2 style="margin: 0; font-size: 20px;">Verificación de Transporte</h2>
            <p style="margin: 0; font-size: 14px;">Municipalidad de Lanús</p>
          </div>
          <div style="padding: 30px;">
            <p style="font-size: 16px;">Hola <strong>' . $nombre . '</strong>,</p>
            <p style="font-size: 15px; line-height: 1.6; color: #333333;">' . $mensajeHTML . '</p>
            <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px; font-size: 13px; color: #555;">
              <p><strong>Dirección Gral. de Movilidad y Transporte</strong></p>
              <p>Municipalidad de Lanús</p>
              <p>☎️ 4357-5100 Int. 7137</p>
              <p>✉️ movilidadytransporte@lanus.gob.ar</p>
            </div>
          </div>
          <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #555;">
            © Municipalidad de Lanús · <a href="https://www.lanus.gob.ar" style="color: #891628; text-decoration: none;">www.lanus.gob.ar</a><br>
            <span style="color: #891628; font-weight: bold;">#LaCiudadQueNosMerecemos</span>
          </div>
        </div>
      </div>';

    // Enviar email real
    mail($persona['email'], $asunto, $cuerpo, $cabeceras);

    // Mostrar vista previa
    echo "<div class='border border-gray-300 rounded-xl p-6 bg-white shadow-md'>
            <div class='flex items-center justify-between mb-4'>
              <div class='flex items-center gap-3'>
                <div class='bg-[#891628] text-white rounded-full h-10 w-10 flex items-center justify-center font-bold text-lg'>" . strtoupper($nombre[0]) . "</div>
                <div>
                  <p class='font-semibold text-gray-800'>$nombre</p>
                  <p class='text-sm text-gray-500'>$email</p>
                </div>
              </div>
              <span class='text-sm bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium'>📤 Enviado</span>
            </div>
            <div class='prose max-w-none'>{$cuerpo}</div>
          </div>";
  }

  echo "</div></div></div>";

} else {
  header('Location: notificar_usuario.php');
  exit;
}
