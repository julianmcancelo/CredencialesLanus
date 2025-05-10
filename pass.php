<?php
require_once 'conexion.php';
require_once 'phpqrcode/qrlib.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
  die("<div class='min-h-screen flex items-center justify-center bg-gray-100 text-center text-red-600 font-semibold text-lg'>⚠️ Token faltante.</div>");
}

$stmt = $pdo->prepare("SELECT ta.habilitacion_id, hg.tipo_transporte, ta.fecha_expiracion FROM tokens_acceso ta JOIN habilitaciones_generales hg ON hg.id = ta.habilitacion_id WHERE ta.token = :token LIMIT 1");
$stmt->execute(['token' => $token]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
  die("<div class='min-h-screen flex items-center justify-center bg-gray-100 text-center text-red-600 font-semibold text-lg'>❌ Token inválido.</div>");
}

if (empty($data['fecha_expiracion']) || strtotime($data['fecha_expiracion']) < time()) {
  die("<div class='min-h-screen flex items-center justify-center bg-yellow-50 text-center text-yellow-700 font-semibold text-lg'>⚠️ Este token ha expirado y no es válido para mostrar la credencial.</div>");
}

$id = $data['habilitacion_id'];
$tipo = strtoupper($data['tipo_transporte']);

$qrPath = "qrs/pass_qr_$id.png";
QRcode::png("https://credenciales.transportelanus.com.ar/credencial.php?token=$token", $qrPath, QR_ECLEVEL_H, 10);
$colorBase = $tipo === 'REMIS' ? 'yellow' : 'red';
$titulo = $tipo === 'REMIS' ? 'Transporte Remis Oficial' : 'Transporte Escolar Habilitado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Verificación Transporte</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(to bottom, #fff8f1, #f3f4f6);
      font-family: 'Segoe UI', sans-serif;
    }
    .glass-card {
      background: #ffffffdd;
      backdrop-filter: blur(10px);
      border-radius: 1.5rem;
      border: 1px solid #e5e7eb;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
    }
    .glass-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 30px;
      background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 10px,
        rgba(0,0,0,0.05) 10px,
        rgba(0,0,0,0.05) 20px
      );
      background-size: 20px 30px;
      z-index: 1;
    }
    .glass-card > * {
      position: relative;
      z-index: 10;
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10">
  <div class="glass-card max-w-sm w-full p-6 text-center border-t-8 border-<?= $colorBase ?>-600">
    <div class="bg-gray-100 rounded-lg shadow-sm p-2 inline-block mb-4">
      <img src="https://www.lanus.gob.ar/img/logo-footer.svg" alt="Lanús" class="h-10 mx-auto" style="filter: brightness(0.15) sepia(1) hue-rotate(330deg) saturate(8) contrast(1.2);" />
    </div>

    <h2 class="text-xl font-bold text-<?= $colorBase ?>-700 mb-1">Verificación de Transporte</h2>
    <p class="text-sm text-gray-600 mb-4"><?= $titulo ?></p>

    <div class="bg-white p-4 rounded-xl shadow-inner border border-gray-200 mb-4">
      <img src="<?= $qrPath ?>" alt="QR" class="w-48 h-48 mx-auto rounded-xl border-4 border-<?= $colorBase ?>-600 shadow-lg" />
    </div>

    <div class="text-xs text-gray-700 leading-5">
      <p><strong>Dirección Gral. de Movilidad y Transporte</strong></p>
      <p>Municipalidad de Lanús</p>
      <p class="mt-1">☎️ 4357-5100 Int. 7137</p>
      <p>✉️ movilidadytransporte@lanus.gob.ar</p>
    </div>

    <div class="mt-4 text-xs font-semibold text-<?= $colorBase ?>-600">#LaCiudadQueNosMerecemos</div>
  </div>
</body>
</html>
