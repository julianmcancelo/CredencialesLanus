<?php
require_once 'conexion.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  die("ID inválido");
}

// Obtener datos generales
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
  die("Habilitación no encontrada");
}

// Personas asociadas
$stmt = $pdo->prepare("
  SELECT hp.id, p.nombre, p.dni, hp.rol, hp.licencia_categoria
  FROM habilitaciones_personas hp
  JOIN personas p ON p.id = hp.persona_id
  WHERE hp.habilitacion_id = ?
");
$stmt->execute([$id]);
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vehículo
$stmt = $pdo->prepare("
  SELECT v.dominio, v.marca, v.modelo
  FROM habilitaciones_vehiculos hv
  JOIN vehiculos v ON v.id = hv.vehiculo_id
  WHERE hv.habilitacion_id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

// Establecimiento
$stmt = $pdo->prepare("
  SELECT e.nombre, e.domicilio
  FROM habilitaciones_establecimientos he
  JOIN establecimientos e ON e.id = he.establecimiento_id
  WHERE he.habilitacion_id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editar Habilitación</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="bg-[#891628] text-white p-4 flex justify-between items-center shadow-lg">
    <h1 class="text-xl font-bold">Editar Habilitación</h1>
    <a href="index.php" class="text-sm underline hover:text-gray-200">← Volver al panel</a>
  </div>

  <div class="max-w-5xl mx-auto mt-6 px-4">
    <div class="bg-white rounded-xl shadow-xl p-6">
      <form action="guardar_edicion.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id']) ?>">

        <h2 class="text-xl font-bold text-[#891628] mb-4">Datos Generales</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Número de Licencia</label>
            <input type="text" name="nro_licencia" value="<?= htmlspecialchars($data['nro_licencia']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
          
          <div>
            <label class="block text-sm font-medium">Año</label>
            <input type="number" name="anio" value="<?= htmlspecialchars($data['anio']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium">Vigencia Inicio</label>
            <input type="date" name="vigencia_inicio" value="<?= htmlspecialchars($data['vigencia_inicio']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium">Vigencia Fin</label>
            <input type="date" name="vigencia_fin" value="<?= htmlspecialchars($data['vigencia_fin']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
<div>
            <label class="block text-sm font-medium">Tipo de Transporte</label>
            <select name="tipo_transporte" onchange="toggleDiscapacidadFields()" class="w-full mt-1 border rounded px-3 py-2">
              <option value="Escolar" <?= $data['tipo_transporte'] === 'Escolar' ? 'selected' : '' ?>>Escolar</option>
              <option value="Remis" <?= $data['tipo_transporte'] === 'Remis' ? 'selected' : '' ?>>Remis</option>
              <option value="Carga y Descarga" <?= $data['tipo_transporte'] === 'Carga y Descarga' ? 'selected' : '' ?>>Carga y Descarga</option>
              <option value="Discapacidad" <?= $data['tipo_transporte'] === 'Discapacidad' ? 'selected' : '' ?>>Estacionamiento por Discapacidad</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium">Estado</label>
            <select name="estado" class="w-full mt-1 border rounded px-3 py-2">
              <option value="ACTIVO" <?= $data['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
              <option value="VENCIDO" <?= $data['estado'] === 'VENCIDO' ? 'selected' : '' ?>>VENCIDO</option>
              <option value="INICIADO" <?= $data['estado'] === 'INICIADO' ? 'selected' : '' ?>>INICIADO</option>
              <option value="EN TRÁMITE" <?= $data['estado'] === 'EN TRÁMITE' ? 'selected' : '' ?>>EN TRÁMITE</option>
            </select>
          </div>
        </div>

        <h2 class="text-xl font-bold text-[#891628] mt-8 mb-4">Personas Asociadas</h2>
        <?php foreach ($personas as $persona): ?>
          <div class="border p-4 rounded mb-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium">Nombre</label>
                <input type="text" disabled value="<?= htmlspecialchars($persona['nombre']) ?>" class="w-full mt-1 border rounded px-3 py-2 bg-gray-100">
              </div>
              <div>
                <label class="block text-sm font-medium">DNI</label>
                <input type="text" disabled value="<?= htmlspecialchars($persona['dni']) ?>" class="w-full mt-1 border rounded px-3 py-2 bg-gray-100">
              </div>
              <div>
                <label class="block text-sm font-medium">Rol</label>
                <select name="personas[<?= $persona['id'] ?>][rol]" class="w-full mt-1 border rounded px-3 py-2">
                  <option value="TITULAR" <?= $persona['rol'] === 'TITULAR' ? 'selected' : '' ?>>Titular</option>
                  <option value="CONDUCTOR" <?= $persona['rol'] === 'CONDUCTOR' ? 'selected' : '' ?>>Conductor</option>
                  <option value="CELADOR" <?= $persona['rol'] === 'CELADOR' ? 'selected' : '' ?>>Celador</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium">Licencia Cat.</label>
                <input type="text" name="personas[<?= $persona['id'] ?>][licencia_categoria]" value="<?= htmlspecialchars($persona['licencia_categoria']) ?>" class="w-full mt-1 border rounded px-3 py-2">
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <h2 class="text-xl font-bold text-[#891628] mt-8 mb-4">Vehículo</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Dominio</label>
            <input type="text" name="vehiculo_dominio" value="<?= htmlspecialchars($vehiculo['dominio']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium">Marca y Modelo</label>
            <input type="text" name="vehiculo_modelo" value="<?= htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
        </div>

        <h2 class="text-xl font-bold text-[#891628] mt-8 mb-4">Establecimiento</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Nombre</label>
            <input type="text" name="establecimiento_nombre" value="<?= htmlspecialchars($establecimiento['nombre']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium">Domicilio</label>
            <input type="text" name="establecimiento_domicilio" value="<?= htmlspecialchars($establecimiento['domicilio']) ?>" class="w-full mt-1 border rounded px-3 py-2">
          </div>
        </div>

        <div class="mt-8 text-right">
          <button type="submit" class="bg-[#891628] text-white px-6 py-2 rounded shadow hover:bg-[#701020] transition">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
