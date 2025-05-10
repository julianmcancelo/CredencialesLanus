<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';

// Si llega POST de fecha y hora, actualizarlas en sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!empty($input['fecha_solicitud']) && !empty($input['hora_solicitud'])) {
        $_SESSION['datos_pdf']['fecha_solicitud'] = $input['fecha_solicitud'];
        $_SESSION['datos_pdf']['hora_solicitud'] = $input['hora_solicitud'];
    }
}

// Ahora sí, trabajar como antes
$datos = $_SESSION['datos_pdf'] ?? null;

if (!$datos) {
    echo json_encode(['success' => false, 'message' => 'Datos de sesión no encontrados.']);
    exit;
}

// Tus variables
$nombre = $datos['nombre_completo'] ?? 'Titular';
$fecha = $datos['fecha_solicitud'] ?? 'Fecha';
$hora = $datos['hora_solicitud'] ?? 'Hora';
$dominio = $datos['dominio'] ?? 'Dominio no registrado';
$tratamiento = $datos['tratamiento'] ?? 'Señor';
$para = $datos['email'] ?? '';

if (empty($para)) {
    echo json_encode(['success' => false, 'message' => 'Correo electrónico no disponible.']);
    exit;
}


// Armar correo
$asunto = "Citación para colocación de oblea";

$mensaje = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Citación a Colocación de Oblea</title>
<style>
body {
  font-family: "Arial", sans-serif;
  background-color: #f4f6f8;
  margin: 0;
  padding: 0;
}
.container {
  max-width: 600px;
  margin: 40px auto;
  background: #ffffff;
  padding: 30px 40px;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.05);
}
.header {
  text-align: center;
  padding-bottom: 20px;
  border-bottom: 2px solid #7B1F3A;
}
.header img {
  max-width: 120px;
  margin-bottom: 10px;
  filter: invert(14%) sepia(51%) saturate(3563%) hue-rotate(-13deg) brightness(92%) contrast(89%);
}
.header h1 {
  font-size: 22px;
  color: #7B1F3A;
  margin: 0;
}
.body {
  padding-top: 20px;
  font-size: 16px;
  color: #333333;
  line-height: 1.6;
}
.body ul {
  margin: 15px 0;
  padding-left: 20px;
}
.body ul li {
  margin-bottom: 8px;
}
.footer {
  margin-top: 30px;
  font-size: 12px;
  text-align: center;
  color: #999999;
  border-top: 1px solid #dddddd;
  padding-top: 20px;
}
.button {
  display: inline-block;
  margin-top: 20px;
  background-color: #7B1F3A;
  color: #ffffff;
  padding: 10px 20px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: bold;
}
</style>
</head>

<body>
<div class="container">
  <div class="header">
    <img src="https://lanus.gob.ar/img/logo-footer.svg" alt="Logo Transporte">
    <h1>Dirección General de Movilidad y Transporte</h1>
  </div>

  <div class="body">
    <p>Estimado/a <strong>' . htmlspecialchars($tratamiento) . ' ' . htmlspecialchars($nombre) . '</strong>,</p>

    <p>Se lo cita para la <strong>colocación de la oblea habilitante de transporte</strong> según los siguientes datos:</p>

    <ul>
      <li><strong>Fecha:</strong> ' . htmlspecialchars($fecha) . '</li>
      <li><strong>Hora:</strong> ' . htmlspecialchars($hora) . ' hs</li>
      <li><strong>Vehículo (Dominio):</strong> ' . htmlspecialchars($dominio) . '</li>
      <li><strong>Dirección:</strong> Hipólito Yrigoyen 3863, Lanús.</li>
    </ul>

    <p>Por favor presentarse puntualmente con el vehículo habilitado.</p>

    <p>Para cualquier consulta, puede comunicarse con nuestra oficina.</p>

    <a href="mailto:transportepublicolanus@gmail.com" class="button">Contactar Oficina</a>
  </div>

  <div class="footer">
    Dirección General de Movilidad y Transporte - Municipalidad de Lanús<br>
    Este correo es informativo. No responder directamente.
  </div>
</div>
</body>
</html>
';




$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: Transporte Lanús <no-reply@transportelanus.com.ar>\r\n";
$headers .= "Reply-To: transportepublicolanus@gmail.com\r\n";

if (mail($para, $asunto, $mensaje, $headers)) {
    // Opcional: marcar como notificado
    $update = $pdo->prepare('UPDATE habilitaciones_generales SET notificado=1 WHERE id=?');
    $update->execute([$habilitacion_id]);
    mostrarResultado(true, 'El correo fue enviado exitosamente.');
} else {
    mostrarResultado(false, 'Error al enviar el correo.');
}

function mostrarResultado($exito, $mensaje) {
    $color = $exito ? 'green' : 'red';
    $emoji = $exito ? '✅' : '❌';

    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Resultado de Envío</title>
      <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100 min-h-screen flex flex-col justify-center items-center p-6">

      <div class="bg-white rounded-lg shadow-lg p-10 max-w-lg w-full text-center">
        <div class="text-5xl mb-4">' . $emoji . '</div>
        <h1 class="text-2xl font-bold text-' . $color . '-600 mb-4">' . ($exito ? '¡Correo Enviado!' : 'Error al Enviar') . '</h1>
        <p class="text-gray-700 mb-6">' . htmlspecialchars($mensaje) . '</p>

        <div class="flex flex-col space-y-4">
          <a href="generarpdf.php" class="bg-gray-700 hover:bg-gray-800 text-white py-3 px-6 rounded-lg font-semibold transition">📄 Volver a Generar PDF</a>
          <a href="index.php" class="bg-gray-700 hover:bg-gray-800 text-white py-3 px-6 rounded-lg font-semibold transition">🏠 Ir al Panel Principal</a>
        </div>
      </div>

    </body>
    </html>';
}
?>