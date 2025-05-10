<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración de la API de Google Gemini
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');
define('GEMINI_API_KEY', 'AIzaSyAZbTf4UvE9n1_4rI9JGRidh1I4P26lO7c');

// Función para procesar el PDF
function procesarPDF($pdfPath) {
    $pdfContent = file_get_contents($pdfPath);

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => "Genera un resumen profesional y detallado del siguiente contenido: " . base64_encode($pdfContent)]
                ]
            ]
        ]
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . GEMINI_API_KEY,
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents(GEMINI_API_URL . '?key=' . GEMINI_API_KEY, false, $context);

    if ($response === false) {
        $errorDetails = error_get_last();
        return ['error' => 'Error al conectar con la API de Google Gemini. ' . ($errorDetails['message'] ?? 'No se pudo obtener el detalle del error.')];
    }

    $result = json_decode($response, true);
    return $result;
}

// Subida de PDF y procesamiento
$resumen = '';
$puntosImportantes = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $pdfFile = $_FILES['pdf_file'];

    if ($pdfFile['error'] === 0 && $pdfFile['type'] === 'application/pdf') {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filePath = $uploadDir . basename($pdfFile['name']);
        move_uploaded_file($pdfFile['tmp_name'], $filePath);

        $analysisResult = procesarPDF($filePath);

        if (isset($analysisResult['error'])) {
            $error = $analysisResult['error'];
        } else {
            $resumen = $analysisResult['contents'][0]['parts'][0]['text'] ?? 'No se generó resumen.';
        }
    } else {
        $error = 'Error al subir el archivo. Asegúrate de que sea un PDF válido.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Resumen Profesional - Google Gemini</title>
</head>
<body class="bg-gradient-to-r from-gray-100 to-gray-200 min-h-screen text-gray-800">
  <div class="container mx-auto py-12">
    <div class="bg-white shadow-lg rounded-lg p-8">
      <h1 class="text-3xl font-bold mb-6 text-center text-blue-600">Generar Resumen Profesional del Documento</h1>

      <?php if ($error): ?>
        <div class="alert alert-danger"> <?= $error ?> </div>
      <?php endif; ?>

      <form action="" method="post" enctype="multipart/form-data" class="mb-6">
        <div class="mb-4">
          <label for="pdf_file" class="block mb-2 font-semibold">Seleccionar PDF:</label>
          <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept="application/pdf" required>
        </div>
        <button type="submit" class="btn btn-primary w-full">Procesar Documento</button>
      </form>

      <?php if ($resumen): ?>
        <div class="bg-blue-50 p-6 rounded-lg shadow mb-6">
          <h2 class="text-xl font-bold mb-4">Resumen del Documento:</h2>
          <p><?= htmlspecialchars($resumen) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
