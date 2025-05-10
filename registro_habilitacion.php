<?php
require_once 'conexion.php';
session_start();

// Verificar acceso solo admin
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
      <p class='text-gray-700'>Esta sección está reservada para administradores. <a href='login.php' class='text-blue-600 underline font-medium'>Iniciá sesión</a> con una cuenta válida.</p>
      <div class='text-sm text-gray-600 border-t pt-4'>
        <p>¿Sos lector invitado? Podés usar tu credencial desde el enlace enviado o pedir asistencia.</p>
        <p class='mt-1 font-semibold text-red-900'>📧 movilidadytransporte@lanus.gob.ar</p>
      </div>
    </div>
  </body>
  </html>";
  exit;
}

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['nro_licencia'], $_POST['resolucion'], $_POST['vigencia_inicio'], $_POST['vigencia_fin'], $_POST['estado'], $_POST['tipo'], $_POST['expte'], $_POST['tipo_transporte'])) {
    $stmt = $pdo->prepare("INSERT INTO habilitaciones_generales (anio, nro_licencia, resolucion, vigencia_inicio, vigencia_fin, estado, tipo, observaciones, expte, tipo_transporte) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      date('Y'),
      $_POST['nro_licencia'],
      $_POST['resolucion'],
      $_POST['vigencia_inicio'],
      $_POST['vigencia_fin'],
      $_POST['estado'],
      $_POST['tipo'],
      $_POST['observaciones'] ?? null,
      $_POST['expte'],
      $_POST['tipo_transporte']
    ]);
    $mensaje = "✅ Habilitación registrada con éxito.";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Habilitación</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-2xl bg-white p-6 rounded-xl shadow-lg">
    <h1 class="text-2xl font-bold text-[#891628] mb-4 text-center">Registrar Nueva Habilitación</h1>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm text-center"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold mb-1">N° de Licencia</label>
        <input name="nro_licencia" required placeholder="Ej: 068-0001/25" class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Resolución</label>
        <input name="resolucion" placeholder="Resolución legal" class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Tipo</label>
        <select name="tipo" required class="w-full border px-3 py-2 rounded shadow-sm">
          <option value="HABILITACION">Habilitación</option>
          <option value="RENOVACION">Renovación</option>
          <option value="CAMBIO MATERIAL">Cambio de Material</option>
          <option value="CAMBIO TITULAR">Cambio de Titular</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Estado</label>
        <select name="estado" required class="w-full border px-3 py-2 rounded shadow-sm">
          <option value="HABILITADO">HABILITADO</option>
          <option value="NO HABILITADO">NO HABILITADO</option>
          <option value="EN TRAMITE">EN TRÁMITE</option>
          <option value="INICIADO">INICIADO</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Tipo de Transporte</label>
        <select name="tipo_transporte" required class="w-full border px-3 py-2 rounded shadow-sm">
          <option value="Escolar">Escolar</option>
          <option value="Remis">Remis</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Vigencia desde</label>
        <input type="date" name="vigencia_inicio" required class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Vigencia hasta</label>
        <input type="date" name="vigencia_fin" required class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">N° de Expediente</label>
        <input name="expte" required placeholder="Ej: 12345-2025" class="w-full border px-3 py-2 rounded shadow-sm">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-semibold mb-1">Observaciones</label>
        <textarea name="observaciones" rows="3" class="w-full border px-3 py-2 rounded shadow-sm"></textarea>
      </div>
      <div class="md:col-span-2 flex justify-end gap-2">
        <a href="index.php" class="px-4 py-2 bg-gray-200 rounded-md">Cancelar</a>
        <button type="submit" class="bg-[#891628] text-white px-6 py-2 rounded hover:bg-red-800 font-semibold">Registrar</button>
      </div>
    </form>

    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-blue-700 hover:underline">← Volver al Panel</a>
    </div>
  </div>
</body>
</html>
