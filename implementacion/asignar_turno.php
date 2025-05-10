<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';

// Función para generar el cuerpo del correo
function generarCorreoTurno($nombreTitular, $marca, $dominio, $fecha, $hora) {
  $fechaFormateada = date('d/m/Y', strtotime($fecha));
  $horaFormateada = date('H:i', strtotime($hora));

  return "
  <html>
  <head>
    <meta charset='UTF-8'>
    <title>Notificación de Turno</title>
    <style>
      body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f3f4f6;
        padding: 0;
        margin: 0;
      }
      .container {
        max-width: 600px;
        margin: auto;
        background-color: #ffffff;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      }
      .header {
        text-align: center;
        margin-bottom: 30px;
      }
      .header img {
        width: 160px;
        margin-bottom: 10px;
      }
      h2 {
        color: #891628;
        font-size: 22px;
        margin-top: 0;
      }
      p {
        font-size: 15px;
        color: #333;
        line-height: 1.6;
        margin: 12px 0;
      }
      .info-box {
        background-color: #fef2f2;
        border-left: 5px solid #dc2626;
        padding: 12px 16px;
        margin: 20px 0;
        font-size: 14px;
        color: #991b1b;
      }
      .footer {
        margin-top: 30px;
        font-size: 13px;
        color: #666;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <div class='container'>
      <div class='header'>
        <img src='https://www.lanus.gob.ar/img/logo-footer.svg' alt='Lanús'>
        <h2>Sr./Sra. {$nombreTitular}</h2>
      </div>

      <p>Le informamos que se le ha asignado un <strong>turno de inspección vehicular</strong> para el automotor:</p>

      <div class='info-box'>
        <strong>Marca:</strong> {$marca}<br>
        <strong>Dominio:</strong> {$dominio}<br>
        <strong>Fecha:</strong> {$fechaFormateada}<br>
        <strong>Hora:</strong> {$horaFormateada} hs
      </div>

      <p>Deberá presentarse en:</p>
      <p><strong>Intendente Manuel Quindimil 857, esquina Jujuy</strong> - Municipio de Lanús.</p>

      <p><strong>Importante:</strong> Es obligatorio presentarse con <strong>DNI</strong> y <strong>Cédula Verde</strong>.</p>

      <p>Por cualquier consulta o cambio en el turno, comuníquese a:</p>
      <p>📧 <strong>transportepublicolanus@gmail.com</strong></p>

      <p style='margin-top: 20px;'>Se solicita <strong>confirmar asistencia</strong> respondiendo a este correo.</p>

      <div class='footer'>
        Dirección Gral. de Movilidad y Transporte<br>
        Subsecretaría de Ordenamiento Urbano<br>
        Municipio de Lanús
      </div>
    </div>
  </body>
  </html>
  ";
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['habilitacion_id'] ?? null;
  $fecha = $_POST['fecha'] ?? null;
  $hora = $_POST['hora'] ?? null;
  $obs = $_POST['observaciones'] ?? '';

  if ($id && $fecha && $hora) {
    try {
      // Guardar turno
      $stmt = $pdo->prepare("INSERT INTO turnos (habilitacion_id, fecha, hora, observaciones) VALUES (?, ?, ?, ?)");
      $stmt->execute([$id, $fecha, $hora, $obs]);

      // Obtener datos del titular y vehículo
      $stmt = $pdo->prepare("
        SELECT p.email, p.nombre, v.marca, v.dominio
        FROM habilitaciones_personas hp
        JOIN personas p ON p.id = hp.persona_id
        JOIN habilitaciones_vehiculos hv ON hv.habilitacion_id = hp.habilitacion_id
        JOIN vehiculos v ON v.id = hv.vehiculo_id
        WHERE hp.habilitacion_id = ? AND hp.rol = 'TITULAR'
        LIMIT 1
      ");
      $stmt->execute([$id]);
      $datos = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($datos && filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
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

        if ($enviado) {
          // Todo ok
          header("Location: index.php?turno=ok");
          exit;
        } else {
          echo "<p style='color: red;'>❌ No se pudo enviar el correo a {$datos['email']}.</p>";
          exit;
        }

      } else {
        echo "<p style='color: red;'>❌ El titular no tiene un email válido registrado.</p>";
        exit;
      }

    } catch (PDOException $e) {
      echo "<p style='color: red;'>Error al guardar el turno: " . $e->getMessage() . "</p>";
      exit;
    }

  } else {
    echo "<p style='color: red;'>❌ Faltan datos del turno (ID, fecha u hora).</p>";
    exit;
  }
}
?>
