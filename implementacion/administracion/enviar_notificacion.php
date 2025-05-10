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
  $cabeceras = "From: transportepublicolanus@gmail.com\r\n";
  $cabeceras .= "MIME-Version: 1.0\r\n";
  $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";

  $logo_url = "https://www.lanus.gob.ar/img/logo-footer.svg";

  echo "<div class='min-h-screen bg-gray-100 py-10 px-4'>
          <div class='max-w-3xl mx-auto bg-white rounded-xl shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-[#891628] mb-8 text-center'>Vista previa de las notificaciones</h1>
            <div class='space-y-10'>";

  foreach ($destinatarios as $persona) {
    $cuerpo = '
      <div style="background: linear-gradient(to bottom, #fff8f1, #f3f4f6); padding: 30px; font-family: \'Segoe UI\', sans-serif;">
        <div style="max-width: 600px; margin: auto; border-radius: 1.5rem; background: #ffffffdd; backdrop-filter: blur(10px); border: 1px solid #e5e7eb; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1); overflow: hidden;">
          <div style="background-color: #891628; padding: 25px; text-align: center; color: white; border-top: 8px solid #891628;">
            <img src="' . $logo_url . '" alt="Lanús" style="height: 50px; filter: brightness(0.15) sepia(1) hue-rotate(330deg) saturate(8) contrast(1.2); margin-bottom: 10px;" />
            <h2 style="margin: 0; font-size: 22px; font-weight: bold;">Verificación de Transporte</h2>
            <p style="margin: 0; font-size: 14px;">Transporte Público Lanús</p>
          </div>

          <div style="padding: 30px;">
            <p style="font-size: 16px; color: #111; margin-bottom: 20px;">Hola <strong>' . htmlspecialchars($persona['nombre']) . '</strong>,</p>
            <p style="font-size: 15px; color: #333; line-height: 1.7;">
              ' . nl2br(htmlspecialchars($mensaje)) . '
            </p>

            <div style="margin-top: 30px; font-size: 13px; color: #555; border-top: 1px solid #ddd; padding-top: 15px;">
              <p><strong>Dirección Gral. de Movilidad y Transporte</strong></p>
              <p>Municipalidad de Lanús</p>
              <p>☎️ 4357-5100 Int. 7137</p>
              <p>✉️ movilidadytransporte@lanus.gob.ar</p>
            </div>
          </div>

          <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #555;">
            © Municipalidad de Lanús - Área Transporte · <a href="https://www.lanus.gob.ar" style="color: #891628; text-decoration: none; font-weight: 600;">www.lanus.gob.ar</a><br>
            <span style="color: #891628; font-weight: bold;">#LaCiudadQueNosMerecemos</span>
          </div>
        </div>
      </div>';

    // mail($persona['email'], $asunto, $cuerpo, $cabeceras); // Producción
    echo "<div class='border border-gray-300 rounded-xl p-6 bg-white shadow-sm'>
            <div class='flex items-center justify-between mb-4'>
              <div class='flex items-center gap-3'>
                <div class='bg-[#891628] text-white rounded-full h-10 w-10 flex items-center justify-center font-bold text-lg'>" . strtoupper($persona['nombre'][0]) . "</div>
                <div>
                  <p class='font-semibold text-gray-800'>" . htmlspecialchars($persona['nombre']) . "</p>
                  <p class='text-sm text-gray-500'>" . htmlspecialchars($persona['email']) . "</p>
                </div>
              </div>
              <span class='text-sm bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium'>📤 Lista para enviar</span>
            </div>
            <div class='prose max-w-none'>" . $cuerpo . "</div>
          </div>";
  }

  echo "</div></div></div>";

} else {
  header('Location: notificar_usuario.php');
  exit;
}
