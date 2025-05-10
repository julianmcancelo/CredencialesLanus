<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'conexion.php';
session_start();

// Función para generar el cuerpo del correo
function generarCorreoTurno($nombreTitular, $marca, $dominio, $fecha, $hora) {
  $fechaFormateada = date('d/m/Y', strtotime($fecha));
  $horaFormateada = date('H:i', strtotime($hora));
  return "
  <html>
  <head>
    <meta charset='UTF-8'>
    <title>Turno Asignado - Municipalidad de Lanús</title>
    <style>
      body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f9fafb;
        margin: 0;
        padding: 0;
      }
      .container {
        max-width: 620px;
        margin: 40px auto;
        background-color: #ffffff;
        border-radius: 12px;
        padding: 30px 40px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        color: #111827;
      }
      .header {
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 20px;
        margin-bottom: 20px;
      }
      .header img {
        width: 140px;
        margin-bottom: 12px;
      }
      h1 {
        color: #891628;
        font-size: 22px;
        margin: 0;
      }
      .section {
        margin-bottom: 20px;
      }
      .info-box {
        background-color: #fef2f2;
        border-left: 5px solid #dc2626;
        padding: 16px;
        font-size: 15px;
        line-height: 1.6;
        border-radius: 6px;
        margin-top: 10px;
      }
      .footer {
        font-size: 13px;
        color: #6b7280;
        text-align: center;
        border-top: 1px solid #e5e7eb;
        padding-top: 20px;
        margin-top: 30px;
      }
    </style>
  </head>
  <body>
    <div class='container'>
      <div class='header'>
        <img src='https://www.lanus.gob.ar/img/logo-footer.svg' alt='Lanús'>
        <h1>Turno de Inspección Asignado</h1>
      </div>

      <div class='section'>
        <p>Hola <strong>{$nombreTitular}</strong>,</p>
        <p>Le informamos que se le ha asignado un <strong>turno de inspección vehicular</strong> para el siguiente automotor:</p>

        <div class='info-box'>
          <p><strong>Marca:</strong> {$marca}</p>
          <p><strong>Dominio:</strong> {$dominio}</p>
          <p><strong>Fecha:</strong> {$fechaFormateada}</p>
          <p><strong>Hora:</strong> {$horaFormateada} hs</p>
        </div>
      </div>

      <div class='section'>
        <p><strong>Dirección:</strong><br>Intendente Manuel Quindimil 857, esquina Jujuy, Lanús.</p>
        <p><strong>Documentación obligatoria:</strong> DNI y Cédula Verde.</p>
        <p>Por dudas o reprogramaciones, comuníquese a:<br><strong>📧 transportepublicolanus@gmail.com</strong></p>
        <p style='margin-top: 10px;'>Por favor, confirme su asistencia respondiendo este correo.</p>
      </div>

      <div class='footer'>
        Dirección Gral. de Movilidad y Transporte<br>
        Subsecretaría de Ordenamiento Urbano<br>
        Municipalidad de Lanús
      </div>
    </div>
  </body>
  </html>";
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['habilitacion_id'] ?? null;
  $fecha = $_POST['fecha'] ?? null;
  $hora = $_POST['hora'] ?? null;
  $obs = $_POST['observaciones'] ?? '';

  if ($id && $fecha && $hora) {
    try {
      // Guardar turno en tabla 'turnos'
      $stmt = $pdo->prepare("INSERT INTO turnos (habilitacion_id, fecha, hora, observaciones) VALUES (?, ?, ?, ?)");
      $stmt->execute([$id, $fecha, $hora, $obs]);

      // Obtener datos para email y verificación
      $stmt = $pdo->prepare("
        SELECT p.email, p.nombre, v.marca, v.dominio, hg.nro_licencia
        FROM habilitaciones_personas hp
        JOIN personas p ON p.id = hp.persona_id
        JOIN habilitaciones_vehiculos hv ON hv.habilitacion_id = hp.habilitacion_id
        JOIN vehiculos v ON v.id = hv.vehiculo_id
        JOIN habilitaciones_generales hg ON hg.id = hp.habilitacion_id
        WHERE hp.habilitacion_id = ? AND hp.rol = 'TITULAR'
        LIMIT 1
      ");
      $stmt->execute([$id]);
      $datos = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($datos && filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        // Enviar correo
        $mensaje = generarCorreoTurno(
          $datos['nombre'],
          $datos['marca'],
          $datos['dominio'],
          $fecha,
          $hora
        );

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Turnos Transporte Escolar <transportepublicolanus@gmail.com>\r\n";

        $enviado = mail($datos['email'], "📅 Turno de Inspección Vehicular", $mensaje, $headers);

        // ✅ Si envío el mail, además registrar en historial de verificaciones
        if ($enviado) {
          $stmtHistorial = $pdo->prepare("
            INSERT INTO verificaciones_historial 
            (nro_licencia, fecha, hora, nombre_titular, dominio, resultado) 
            VALUES (?, ?, ?, ?, ?, 'PENDIENTE')
          ");
          $stmtHistorial->execute([
            $datos['nro_licencia'],
            $fecha,
            $hora,
            $datos['nombre'],
            $datos['dominio']
          ]);

          header("Location: index.php?turno=ok");
          exit;
        } else {
          echo "<p style='color:red;'>❌ Error al enviar correo a {$datos['email']}.</p>";
          exit;
        }
      } else {
        echo "<p style='color:red;'>❌ Titular sin email válido.</p>";
        exit;
      }
    } catch (PDOException $e) {
      echo "<p style='color:red;'>❌ Error al guardar: " . $e->getMessage() . "</p>";
      exit;
    }
  } else {
    echo "<p style='color:red;'>❌ Faltan datos del turno.</p>";
    exit;
  }
}
?>
