<?php
// Configuracion General
require_once 'conexion.php';

$modoPrueba = true;
$correoBcc = "juliancancelo@gmail.com";
$ubicacion = "Av. Intendente Manuel Quindimi 857, Esq. Jujuy - Lanús Oeste.";
$urlRegreso = "index.php";
$nombreInstitucion = "Dirección General de Movilidad y Transporte - Municipio de Lanús";
$logoInstitucion = "https://lanus.gob.ar/img/logo-footer.svg";

// Parámetros
if (isset($_GET['prueba'])) {
    $modoPrueba = $_GET['prueba'] == 1 ? true : false;
}

$fechaSeleccionada = $_GET['fecha'] ?? date('Y-m-d');
$confirmarEnvio = isset($_GET['enviar']) && $_GET['enviar'] === '1';

// Errores visibles
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función envío correo
function enviarCorreo($para, $asunto, $mensajeHTML, $modoPrueba, $correoBcc) {
    if ($modoPrueba) {
        $para = $correoBcc;
    }
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Transporte Lanús <transportepublicolanus@gmail.com>\r\n";
    $headers .= "Bcc: $correoBcc\r\n";
    return mail($para, $asunto, $mensajeHTML, $headers);
}

// Traer turnos
$sql = "SELECT 
            t.id AS turno_id,
            t.fecha,
            t.hora,
            h.nro_licencia,
            p.nombre AS titular,
            p.email AS email
        FROM turnos t
        INNER JOIN habilitaciones_generales h ON t.habilitacion_id = h.id
        INNER JOIN habilitaciones_personas hp ON hp.habilitacion_id = h.id AND hp.rol = 'TITULAR'
        INNER JOIN personas p ON p.id = hp.persona_id
        WHERE t.fecha = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fechaSeleccionada]);
$turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEnviados = 0;
$totalErrores = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recordatorios de Turnos</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<style>
  body { font-family: 'Inter', sans-serif; }
</style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

<!-- Navbar -->
<header class="bg-[#7B1F3A] p-4 flex justify-between items-center shadow-md">
  <div class="flex items-center gap-3">
    <img src="<?php echo $logoInstitucion; ?>" alt="Logo" class="h-10">
    <h1 class="text-white font-bold text-lg"><?php echo $nombreInstitucion; ?></h1>
  </div>
  <a href="<?php echo $urlRegreso; ?>" class="text-white font-semibold hover:underline">← Volver</a>
</header>

<!-- Modo prueba / real -->
<?php if (isset($_GET['prueba'])): ?>
<div class="text-center mt-4">
  <span class="inline-flex items-center gap-2 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-2 rounded shadow">
    <svg class="animate-bounce w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 4h18"></path></svg>
    <?php echo $modoPrueba ? '🧪 Modo Prueba Activo' : '✅ Modo Real Activo'; ?>
  </span>
</div>
<?php endif; ?>

<!-- Contenido -->
<main class="flex-1 max-w-6xl mx-auto p-6">

  <h2 class="text-3xl font-bold text-[#7B1F3A] text-center mb-8">Recordatorios de Turnos</h2>

  <!-- Selector de fecha -->
  <form method="get" class="flex flex-col md:flex-row justify-center items-center gap-4 mb-10">
    <input type="date" name="fecha" value="<?php echo htmlspecialchars($fechaSeleccionada); ?>" class="border border-gray-300 rounded p-2 shadow-sm focus:ring-2 focus:ring-[#7B1F3A]" required>
    <div class="flex gap-2">
      <button type="submit" class="bg-[#7B1F3A] hover:bg-red-700 text-white px-6 py-2 rounded-lg shadow font-semibold">🔎 Buscar Turnos</button>
      <a href="?prueba=<?php echo $modoPrueba ? '0' : '1'; ?>&fecha=<?php echo urlencode($fechaSeleccionada); ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg shadow font-semibold">
        <?php echo $modoPrueba ? '✅ Modo Real' : '🧪 Modo Prueba'; ?>
      </a>
    </div>
  </form>

  <!-- Turnos -->
  <?php if (!$turnos): ?>
    <div class="text-center text-red-500 font-semibold text-lg">🚫 No hay turnos para la fecha <?php echo htmlspecialchars($fechaSeleccionada); ?>.</div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($turnos as $turno): 
        $correoDestino = $modoPrueba ? $correoBcc : $turno['email'];
        $asunto = "📋 Recordatorio: Turno de Verificación Vehicular";

        $mensaje = "
        <div style='font-family: Arial, sans-serif; background: #ffffff; padding: 24px; border-radius: 10px; border: 1px solid #ddd; max-width:600px; margin:auto;'>
          <h2 style='color: #7B1F3A; text-align: center;'>📋 Recordatorio de Turno</h2>
          <p>Estimado/a <strong>{$turno['titular']}</strong>,</p>
          <p>Le recordamos que tiene programado un turno de verificación vehicular:</p>
          <table style='width:100%; margin-top:20px; font-size:15px;'>
            <tr><td>📋 <strong>N° Licencia:</strong></td><td>{$turno['nro_licencia']}</td></tr>
            <tr><td>🗓️ <strong>Fecha:</strong></td><td>{$turno['fecha']}</td></tr>
            <tr><td>🕗 <strong>Hora:</strong></td><td>{$turno['hora']}</td></tr>
            <tr><td>📍 <strong>Ubicación:</strong></td><td>$ubicacion <br><a href='https://www.google.com/maps/search/?api=1&query=" . urlencode($ubicacion) . "' target='_blank' style='color:#7B1F3A; text-decoration:underline;'>📍 Ver en Google Maps</a></td></tr>
          </table>
          <p style='margin-top:20px;'>➡️ Presentarse 10 minutos antes con documentación.</p>
          <p>Si no puede asistir, por favor comuníquese para reprogramar.</p>
          <hr style='margin:30px 0;'>
          <p style='text-align:center; font-size:12px; color:#888;'>Este correo fue enviado automáticamente. No responder.</p>
        </div>";

        ?>
      <div class="bg-white border border-gray-200 p-6 rounded-lg shadow hover:shadow-lg transition">
        <p class="text-sm text-gray-500 mb-2"><strong>Destinatario:</strong> <?php echo htmlspecialchars($correoDestino); ?></p>
        <?php echo $mensaje; ?>
        <?php if ($confirmarEnvio && $correoDestino): ?>
          <?php if (enviarCorreo($correoDestino, $asunto, $mensaje, $modoPrueba, $correoBcc)): ?>
            <p class="mt-4 text-green-600 font-semibold">✅ Correo enviado correctamente.</p>
          <?php else: ?>
            <p class="mt-4 text-red-600 font-semibold">⚠️ Error al enviar el correo.</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>

    <!-- Confirmar envío -->
    <?php if (!$confirmarEnvio): ?>
    <div class="text-center mt-12">
      <a href="?enviar=1&fecha=<?php echo urlencode($fechaSeleccionada); ?>&prueba=<?php echo $modoPrueba ? '1' : '0'; ?>" class="inline-block bg-green-600 hover:bg-green-700 text-white text-lg font-bold px-10 py-4 rounded-full shadow-lg">✅ Confirmar Envío de Correos</a>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<!-- Footer -->
<footer class="text-center text-gray-500 text-sm py-6 mt-10 border-t border-gray-200">
  <?php echo $nombreInstitucion; ?> © <?php echo date('Y'); ?> - Todos los derechos reservados.
</footer>

</body>
</html>
