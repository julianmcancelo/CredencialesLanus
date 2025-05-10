<?php
require_once 'vendor/autoload.php'; // Asegurate de tener phpword instalado

use PhpOffice\PhpWord\TemplateProcessor;

// Datos de ejemplo que luego vendran de la base de datos
$datos = [
    'nombre_completo' => 'Gabriela Ana Prampolini',
    'dni' => '17587488',
    'servicio' => 'titular en el Servicio de Remis',
    'marca' => 'FIAT',
    'dominio' => 'AB356QR',
    'expediente' => '000016-2024',
    'resolucion_numero' => '0136',
    'fecha_vigencia' => '11 de MARZO de 2025',
    'fecha_actual' => formatearFechaActual()
];

// Cargar plantilla
$template = new TemplateProcessor('plantillas/comparecencia_plantilla.docx');

// Reemplazar placeholders
foreach ($datos as $clave => $valor) {
    $template->setValue($clave, htmlspecialchars($valor));
}

// Guardar el archivo temporalmente
$nombreArchivo = 'comparecencia_' . str_replace(' ', '_', $datos['nombre_completo']) . '.docx';
$tempFile = tempnam(sys_get_temp_dir(), 'docx');
$template->saveAs($tempFile);

// Enviar encabezados para descarga
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFile));
readfile($tempFile);
unlink($tempFile);
exit;

// Funciones auxiliares
function formatearFechaActual() {
    $meses = [
        '01' => 'ENERO', '02' => 'FEBRERO', '03' => 'MARZO', '04' => 'ABRIL',
        '05' => 'MAYO', '06' => 'JUNIO', '07' => 'JULIO', '08' => 'AGOSTO',
        '09' => 'SEPTIEMBRE', '10' => 'OCTUBRE', '11' => 'NOVIEMBRE', '12' => 'DICIEMBRE'
    ];
    $dia = date('d');
    $mes = $meses[date('m')];
    $anio = date('Y');
    return "$dia de $mes de $anio";
}
