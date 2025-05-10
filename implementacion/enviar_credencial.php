<?php
require_once 'conexion.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$email = $_POST['email'] ?? '';
$id = $_POST['id'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_numeric($id)) {
  die("⚠️ Datos inválidos.");
}

try {
  $stmt = $pdo->prepare("
    SELECT hg.*, 
           p.nombre AS titular_nombre, p.dni AS titular_dni, p.cuit, p.foto_url AS titular_foto,
           c.nombre AS conductor_nombre, c.dni AS conductor_dni, c.foto_url AS conductor_foto,
           hc.licencia_categoria,
           ce.nombre AS celador_nombre, ce.dni AS celador_dni,
           e.nombre AS escuela_nombre, e.domicilio AS escuela_domicilio, e.localidad, e.latitud, e.longitud, e.direccion,
           v.marca, v.modelo, v.motor, v.asientos
    FROM habilitaciones_generales hg
    LEFT JOIN habilitaciones_personas ht ON ht.habilitacion_id = hg.id AND ht.rol = 'TITULAR'
    LEFT JOIN personas p ON p.id = ht.persona_id
    LEFT JOIN habilitaciones_personas hc ON hc.habilitacion_id = hg.id AND hc.rol = 'CONDUCTOR'
    LEFT JOIN personas c ON c.id = hc.persona_id
    LEFT JOIN habilitaciones_personas hce ON hce.habilitacion_id = hg.id AND hce.rol = 'CELADOR'
    LEFT JOIN personas ce ON ce.id = hce.persona_id
    LEFT JOIN habilitaciones_establecimientos he ON he.habilitacion_id = hg.id
    LEFT JOIN establecimientos e ON e.id = he.establecimiento_id
    LEFT JOIN habilitaciones_vehiculos hv ON hv.habilitacion_id = hg.id
    LEFT JOIN vehiculos v ON v.id = hv.vehiculo_id
    WHERE hg.id = :id
  ");
  $stmt->execute(['id' => $id]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$data) {
    die("❌ No se encontraron datos para esta habilitación.");
  }

  ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <style>
    body { background-color: #f4f2f1; font-family: 'Segoe UI', sans-serif; }
    .credencial { box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); border-radius: 1rem; overflow: hidden; background-color: #ffffff; padding: 1.5rem; max-width: 800px; margin: auto; }
    .header-lanus { background-color: #00adee; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; border-radius: 0.75rem; }
    .institucional { border-bottom: 3px solid #891628; background-color: #ffffff; padding: 1rem; margin-top: 0.5rem; }
    .institucional p { color: #891628; font-weight: bold; margin: 0; }
    .datos-estado { background-color: #fdfafa; padding: 1rem 1.5rem; display: flex; flex-wrap: wrap; border-bottom: 1px solid #ddd; font-size: 0.875rem; }
    .datos-estado div { width: 50%; margin-bottom: 0.5rem; font-weight: 600; text-transform: uppercase; color: #444; }
    .datos-estado span { color: #891628; font-weight: bold; }
    .info-vehiculo { padding: 1.5rem; display: flex; justify-content: space-between; gap: 2rem; color: #333; font-size: 0.875rem; }
    .foto img { width: 80px; height: 80px; object-fit: cover; border-radius: 9999px; border: 2px solid #891628; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); background-color: #fff; }
    .seccion { padding: 1rem 1.5rem; font-size: 0.875rem; }
    .seccion h3 { color: #891628; font-weight: bold; margin-bottom: 0.25rem; }
    .seccion p { text-transform: uppercase; margin-bottom: 0.2rem; }
    .legal { background-color: #f8f8f8; border-top: 1px solid #ccc; text-align: center; font-size: 0.75rem; color: #555; padding: 1rem; margin-top: 1rem; }
  </style>
</head>
<body>
  <div class="credencial">
    <div class="header-lanus">
      <h1>#LaCiudadQueNosMerecemos</h1>
      <img src="https://www.lanus.gob.ar/img/logo-footer.svg" class="w-32" />
    </div>

    <div class="institucional">
      <div style="display: flex; justify-content: space-between;">
        <div>
          <p>Lanús</p>
          <p>Gobierno</p>
        </div>
        <div style="text-align: right;">
          <p>Dirección Gral. de Movilidad y Transporte</p>
          <p>Subsecretaría de Ordenamiento Urbano</p>
        </div>
      </div>
    </div>

    <div class="datos-estado">
      <div>N° de Licencia: <span><?= $data['nro_licencia'] ?></span></div>
      <div>Resolución: <span><?= $data['resolucion'] ?></span></div>
      <div>Vigencia: <span><?= $data['vigencia_inicio'] ?> - <?= $data['vigencia_fin'] ?></span></div>
      <div>Estado: <span><?= $data['estado'] ?></span></div>
    </div>

    <div class="info-vehiculo">
      <div>
        <p>Titular: <?= $data['titular_nombre'] ?></p>
        <p>DNI: <?= $data['titular_dni'] ?> - CUIT: <?= $data['cuit'] ?></p>
        <p>Marca: <?= $data['marca'] ?> - Modelo: <?= $data['modelo'] ?></p>
        <p>Motor: <?= $data['motor'] ?></p>
        <p>Asientos: <?= $data['asientos'] ?></p>
      </div>
      <div class="foto">
        <?php if (!empty($data['titular_foto'])): ?>
          <img src="<?= $data['titular_foto'] ?>" />
        <?php endif; ?>
      </div>
    </div>

    <div class="seccion">
      <h3>Establecimiento Educativo</h3>
      <p><strong>Nombre:</strong> <?= $data['escuela_nombre'] ?></p>
      <p><strong>Domicilio:</strong> <?= $data['escuela_domicilio'] ?> - <?= $data['localidad'] ?></p>
      <?php if (!empty($data['direccion'])): ?>
        <p><strong>Dirección exacta:</strong> <?= $data['direccion'] ?></p>
      <?php endif; ?>
    </div>

    <div class="seccion">
      <h3>Conductor Autorizado</h3>
      <p><?= $data['conductor_nombre'] ?> - DNI: <?= $data['conductor_dni'] ?></p>
      <p>Licencia: <?= $data['licencia_categoria'] ?></p>
      <?php if (!empty($data['conductor_foto'])): ?>
        <div class="foto">
          <img src="<?= $data['conductor_foto'] ?>" />
        </div>
      <?php endif; ?>
    </div>

    <div class="seccion">
      <h3>Celador Autorizado</h3>
      <p><?= $data['celador_nombre'] ?> - DNI: <?= $data['celador_dni'] ?></p>
    </div>

    <div class="legal">
      <p>El presente certificado solo es válido junto a la VTV y seguro al día.</p>
      <p>Válido para el Ciclo Lectivo <?= $data['anio'] ?>. ART. 9 RES 122/18</p>
    </div>
  </div>
</body>
</html>
<?php
  $html_pdf = ob_get_clean();

  $dompdf = new Dompdf();
  $dompdf->loadHtml($html_pdf);
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  $pdf_path = __DIR__ . "/pdfs/credencial_$id.pdf";
  file_put_contents($pdf_path, $dompdf->output());

  echo "✅ PDF generado correctamente en: $pdf_path";

} catch (PDOException $e) {
  echo "❌ Error en la base de datos: " . $e->getMessage();
}
