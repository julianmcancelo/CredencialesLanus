<?php
require_once 'conexion.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
  mostrarError("Acceso denegado. Token faltante.");
}

// Verificar si el token existe y obtener fecha de expiración
$stmt = $pdo->prepare("SELECT ta.habilitacion_id, ta.fecha_expiracion, p.email FROM tokens_acceso ta LEFT JOIN habilitaciones_personas hp ON hp.habilitacion_id = ta.habilitacion_id AND hp.rol = 'TITULAR' LEFT JOIN personas p ON p.id = hp.persona_id WHERE ta.token = :token LIMIT 1");
$stmt->execute(['token' => $token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
  mostrarError("Token inválido.");
}

$fechaExpiracion = $tokenData['fecha_expiracion'] ?? null;
if (!$fechaExpiracion || strtotime($fechaExpiracion) === false || strtotime($fechaExpiracion) < time()) {
}

$id = $tokenData['habilitacion_id'];

function mostrarError($mensaje) {
  echo "<html><head><title>Acceso denegado</title>
        <link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet' />
        </head><body class='bg-gradient-to-br from-red-100 to-red-50 flex items-center justify-center min-h-screen'>
        <div class='bg-white shadow-xl rounded-2xl p-8 max-w-lg w-full text-center border border-red-200'>
          <div class='text-red-600 mb-4'>
            <svg xmlns='http://www.w3.org/2000/svg' class='h-16 w-16 mx-auto' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
              <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10.29 3.86L1.82 18a1.5 1.5 0 001.29 2.25h18.78a1.5 1.5 0 001.29-2.25L13.71 3.86a1.5 1.5 0 00-2.42 0z' />
            </svg>
          </div>
          <h1 class='text-2xl font-bold text-red-700 mb-3'>Acceso denegado</h1>
          <p class='text-gray-700 mb-4'>$mensaje</p>
          <div class='text-sm text-gray-600 bg-red-50 border border-red-200 p-4 rounded-lg mt-4'>
            <p>Si creés que esto es un error, comunicate con nosotros:</p>
            <p class='mt-2'><strong>Teléfono:</strong> 4357-5100 (Interno 7137)</p>
            <p><strong>Correo:</strong> movilidadytransporte@lanus.gob.ar</p>
          </div>
          <a href='https://www.lanus.gob.ar' class='mt-4 inline-block text-white bg-red-600 hover:bg-red-700 px-5 py-2 rounded-full shadow transition'>Volver al inicio</a>
        </div>
        </body></html>";
  exit;
}

// Cargar datos de la habilitación
$sql = "
SELECT hg.*, hg.tipo_transporte,
       p.nombre AS titular_nombre, p.dni AS titular_dni, p.cuit, p.foto_url AS titular_foto,
       c.nombre AS conductor_nombre, c.dni AS conductor_dni, c.foto_url AS conductor_foto,
       hc.licencia_categoria,
       ce.nombre AS celador_nombre, ce.dni AS celador_dni,
       e.nombre AS escuela_nombre, e.domicilio AS escuela_domicilio, e.localidad, e.latitud, e.longitud, e.direccion,
       r.nombre AS remiseria_nombre, r.direccion AS remiseria_direccion, r.localidad AS remiseria_localidad, r.latitud AS remiseria_latitud, r.longitud AS remiseria_longitud,
       v.marca, v.modelo, v.motor, v.asientos
FROM habilitaciones_generales hg
LEFT JOIN habilitaciones_personas ht ON ht.habilitacion_id = hg.id AND ht.rol = 'TITULAR'
LEFT JOIN personas p ON p.id = ht.persona_id
LEFT JOIN habilitaciones_personas hc ON hc.habilitacion_id = hg.id AND hc.rol = 'CONDUCTOR'
LEFT JOIN personas c ON c.id = hc.persona_id
LEFT JOIN habilitaciones_personas hce ON hce.habilitacion_id = hg.id AND hce.rol = 'CELADOR'
LEFT JOIN personas ce ON ce.id = hce.persona_id
LEFT JOIN habilitaciones_establecimientos he ON he.habilitacion_id = hg.id
LEFT JOIN establecimientos e ON e.id = he.establecimiento_id AND he.tipo = 'establecimiento'
LEFT JOIN remiserias r ON r.id = he.establecimiento_id AND he.tipo = 'remiseria'
LEFT JOIN habilitaciones_vehiculos hv ON hv.habilitacion_id = hg.id
LEFT JOIN vehiculos v ON v.id = hv.vehiculo_id
WHERE hg.id = :id
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
  mostrarError("No se encontró la habilitación.");
}

$esRemis = strtoupper($data['tipo_transporte']) === 'REMIS';
$tokenUrl = 'https://credenciales.transportelanus.com.ar/credencial.php?token=' . urlencode($token);

include $esRemis ? 'credencial_remis.php' : 'credencial_escolar.php';
