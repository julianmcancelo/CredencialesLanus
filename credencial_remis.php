<?php if (!isset($data)) { echo "Error: Datos no disponibles."; exit; } ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Credencial Remis</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
  <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden border border-yellow-400">
    <!-- Encabezado -->
    <div class="bg-yellow-500 text-white px-6 py-4 flex justify-between items-center">
      <h1 class="text-lg sm:text-2xl font-bold">#LaCiudadQueNosMerecemos</h1>
      <img src="https://www.lanus.gob.ar/img/logo-footer.svg" class="w-28 sm:w-32" />
    </div>

    <!-- Datos institucionales -->
    <div class="bg-yellow-100 text-gray-800 px-6 py-3 text-sm sm:text-base font-semibold flex justify-between">
      <div>
        <p>Lanús</p>
        <p>Gobierno</p>
      </div>
      <div class="text-right">
        <p>Dirección Gral. de Movilidad y Transporte</p>
        <p>Subsecretaría de Ordenamiento Urbano</p>
      </div>
    </div>

    <!-- Información principal -->
    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm sm:text-base">
      <div>
        <p><strong>Nº de Licencia:</strong> <?= $data['nro_licencia'] ?></p>
        <p><strong>Resolución:</strong> <?= $data['resolucion'] ?></p>
        <p><strong>Vigencia:</strong> <?= $data['vigencia_inicio'] ?> - <?= $data['vigencia_fin'] ?></p>
        <p><strong>Estado:</strong> <span class="text-green-700 font-semibold"><?= $data['estado'] ?></span></p>
      </div>
      <div class="text-center">
        <canvas id="qrcode" width="140" height="140" class="mx-auto"></canvas>
        <p class="mt-1 text-xs text-gray-500">Escaneá para verificar</p>
      </div>
    </div>

<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Verifica y convierte la fecha de vencimiento
if (!empty($data['vigencia_fin'])) {
  $vigenciaFin = DateTime::createFromFormat('Y-m-d', $data['vigencia_fin']);
  $hoy = new DateTime();

  if ($vigenciaFin && $vigenciaFin < $hoy): ?>
    <div class="bg-red-700 text-white px-6 py-6 text-center text-xl font-bold animate-pulse">
      ❌ LICENCIA VENCIDA ❌<br>
      Dirigite a la Dirección Gral. de Movilidad y Transporte para su renovación.<br>
      <span class="text-sm font-normal block mt-2">Correo: movilidadytransporte@lanus.gob.ar</span>
    </div>
<?php
  endif;
}
?>

    <!-- Titular y vehículo -->
    <div class="border-t px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
      <div>
        <h3 class="text-yellow-700 font-bold mb-1">Titular</h3>
        <p><?= $data['titular_nombre'] ?> - DNI: <?= $data['titular_dni'] ?></p>
        <p>CUIT: <?= $data['cuit'] ?></p>
        <p>Marca: <?= $data['marca'] ?> - Modelo: <?= $data['modelo'] ?></p>
        <p>Motor: <?= $data['motor'] ?> - Asientos: <?= $data['asientos'] ?></p>
      </div>
      <div class="flex justify-center">
        <img src="<?= $data['titular_foto'] ?>" class="w-24 h-24 object-cover rounded-full border-2 border-yellow-700 shadow-md" />
      </div>
    </div>

    <!-- Remisería -->
    <div class="border-t px-6 py-4">
      <h3 class="text-yellow-700 font-bold mb-2">Remisería Asociada</h3>
      <p><strong>Nombre:</strong> <?= $data['remiseria_nombre'] ?></p>
      <p><strong>Dirección:</strong> <?= $data['remiseria_direccion'] ?> - <?= $data['remiseria_localidad'] ?></p>
    </div>

    <!-- Conductor -->
    <div class="border-t px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
      <div>
        <h3 class="text-yellow-700 font-bold mb-1">Chofer Autorizado</h3>
        <p><?= $data['conductor_nombre'] ?> - DNI: <?= $data['conductor_dni'] ?></p>
        <p class="text-sm text-gray-600 uppercase">Licencia: <?= $data['licencia_categoria'] ?></p>
      </div>
      <div class="flex justify-center">
        <img src="<?= $data['conductor_foto'] ?>" class="w-20 h-20 object-cover rounded-full border-2 border-yellow-700 shadow-md" />
      </div>
    </div>

    <!-- Información legal -->
    <div class="text-center text-xs text-gray-600 px-6 py-3 bg-gray-50 border-t">
      <p>Este certificado es válido solo junto con VTV y seguro al día.</p>
      <p>Válido mientras la habilitación se encuentre vigente.</p>
    </div>

    <!-- Botón imprimir -->
    <div class="text-center py-4 no-print">
      <button onclick="window.print()" class="px-5 py-2 bg-yellow-700 text-white rounded shadow hover:bg-yellow-800 transition">🖨️ Imprimir</button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
  <script>
    new QRious({
      element: document.getElementById('qrcode'),
      value: '<?= $tokenUrl ?>',
      size: 140,
      level: 'H'
    });
  </script>
</body>
</html>
