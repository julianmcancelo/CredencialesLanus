<?php
// Mostrar errores PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';

// Función para escapar HTML
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Obtener ID
$id = $_GET['id'] ?? ($_POST['id'] ?? null);
if (!$id || !is_numeric($id)) {
    die("ID inválido.");
}

// Si viene por POST (guardar cambios)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Guardar datos generales
        $stmt = $pdo->prepare("UPDATE habilitaciones_generales SET nro_licencia=?, anio=?, vigencia_inicio=?, vigencia_fin=?, tipo_transporte=?, estado=? WHERE id=?");
        $stmt->execute([
            $_POST['nro_licencia'], $_POST['anio'], $_POST['vigencia_inicio'],
            $_POST['vigencia_fin'], $_POST['tipo_transporte'], $_POST['estado'], $id
        ]);

        // Actualizar token
        $stmt = $pdo->prepare("UPDATE tokens_acceso SET fecha_expiracion=? WHERE habilitacion_id=?");
        $stmt->execute([$_POST['vigencia_fin'], $id]);

        // Actualizar personas
        foreach ($_POST['personas'] as $pid => $datos) {
            $stmt = $pdo->prepare("UPDATE habilitaciones_personas SET rol=?, licencia_categoria=? WHERE id=?");
            $stmt->execute([$datos['rol'], $datos['licencia_categoria'], $pid]);
        }

        // Actualizar vehículo (sin verificación por ahora)
        $stmt = $pdo->prepare("
            UPDATE vehiculos SET dominio=?, marca=?, modelo=?, motor=?, asientos=?, inscripcion_inicial=?
            WHERE id=(SELECT vehiculo_id FROM habilitaciones_vehiculos WHERE habilitacion_id=? LIMIT 1)
        ");
        $stmt->execute([
            $_POST['vehiculo_dominio'],
            $_POST['vehiculo_marca'],
            $_POST['vehiculo_modelo'],
            $_POST['vehiculo_motor'],
            $_POST['vehiculo_asientos'],
            $_POST['vehiculo_inscripcion_inicial'],
            $id
        ]);

        // Actualizar establecimiento
        $stmt = $pdo->prepare("
            UPDATE establecimientos SET nombre=?, domicilio=?
            WHERE id=(SELECT establecimiento_id FROM habilitaciones_establecimientos WHERE habilitacion_id=? LIMIT 1)
        ");
        $stmt->execute([
            $_POST['establecimiento_nombre'],
            $_POST['establecimiento_domicilio'],
            $id
        ]);

        $pdo->commit();
        header("Location: index.php?msg=guardado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al guardar: " . $e->getMessage());
    }
}

// Si viene por GET (mostrar formulario)
$stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE id=?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT hp.id, p.nombre, p.dni, hp.rol, hp.licencia_categoria
    FROM habilitaciones_personas hp
    JOIN personas p ON p.id=hp.persona_id
    WHERE hp.habilitacion_id=?");
$stmt->execute([$id]);
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT v.dominio, v.marca, v.modelo, v.motor, v.asientos, v.inscripcion_inicial
    FROM habilitaciones_vehiculos hv
    JOIN vehiculos v ON v.id=hv.vehiculo_id
    WHERE hv.habilitacion_id=? LIMIT 1");
$stmt->execute([$id]);
$vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT e.nombre, e.domicilio
    FROM habilitaciones_establecimientos he
    JOIN establecimientos e ON e.id=he.establecimiento_id
    WHERE he.habilitacion_id=? LIMIT 1");
$stmt->execute([$id]);
$establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Habilitación</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-[#891628] text-white p-4 flex justify-between items-center shadow">
  <h1 class="text-xl font-bold">Editar Habilitación</h1>
  <a href="index.php" class="text-sm underline hover:text-gray-300">← Volver</a>
</header>
<main class="p-6">
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'guardado'): ?>
    <script>
    Swal.fire({
      icon: 'success',
      title: '¡Cambios guardados!',
      text: 'La habilitación fue actualizada correctamente.',
      timer: 2500,
      showConfirmButton: false
    });
    </script>
  <?php endif; ?>
<main class="w-full max-w-6xl mx-auto mt-6 px-4 sm:px-6 lg:px-8">
  <form method="POST" class="bg-white p-6 rounded-lg shadow space-y-6">

    <input type="hidden" name="id" value="<?= e($data['id']) ?>">

    <!-- Datos generales -->
    <section>
      <h2 class="text-xl font-bold text-[#891628] mb-4">📄 Datos Generales</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div><label>Licencia</label><input type="text" name="nro_licencia" value="<?= e($data['nro_licencia']) ?>" class="w-full border rounded p-2" required></div>
        <div><label>Año</label><input type="number" name="anio" value="<?= e($data['anio']) ?>" class="w-full border rounded p-2" required></div>
        <div><label>Vigencia desde</label><input type="date" name="vigencia_inicio" value="<?= e($data['vigencia_inicio']) ?>" class="w-full border rounded p-2" required></div>
        <div><label>Vigencia hasta</label><input type="date" name="vigencia_fin" value="<?= e($data['vigencia_fin']) ?>" class="w-full border rounded p-2" required></div>
        <div>
          <label>Tipo de transporte</label>
          <select name="tipo_transporte" class="w-full border rounded p-2">
            <option value="Escolar" <?= $data['tipo_transporte'] === 'Escolar' ? 'selected' : '' ?>>Escolar</option>
            <option value="Remis" <?= $data['tipo_transporte'] === 'Remis' ? 'selected' : '' ?>>Remis</option>
          </select>
        </div>
        <div>
          <label>Estado</label>
          <select name="estado" class="w-full border rounded p-2">
            <?php foreach (['HABILITADO', 'EN TRAMITE', 'VENCIDO', 'INICIADO'] as $estado): ?>
              <option value="<?= $estado ?>" <?= $data['estado'] === $estado ? 'selected' : '' ?>><?= ucfirst(strtolower($estado)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </section>

    <!-- Personas -->
    <section>
      <h2 class="text-xl font-bold text-[#891628] mb-4">👥 Personas</h2>
      <?php foreach ($personas as $p): ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded bg-gray-50 mb-4">
          <input type="hidden" name="personas[<?= e($p['id']) ?>][id]" value="<?= e($p['id']) ?>">
          <div><label>Nombre</label><input type="text" value="<?= e($p['nombre']) ?>" disabled class="w-full bg-gray-100 p-2 rounded"></div>
          <div><label>DNI</label><input type="text" value="<?= e($p['dni']) ?>" disabled class="w-full bg-gray-100 p-2 rounded"></div>
          <div>
            <label>Rol</label>
            <select name="personas[<?= e($p['id']) ?>][rol]" class="w-full border p-2 rounded">
              <option value="TITULAR" <?= $p['rol'] === 'TITULAR' ? 'selected' : '' ?>>Titular</option>
              <option value="CONDUCTOR" <?= $p['rol'] === 'CONDUCTOR' ? 'selected' : '' ?>>Chofer</option>
              <option value="CELADOR" <?= $p['rol'] === 'CELADOR' ? 'selected' : '' ?>>Celador</option>
            </select>
          </div>
          <div><label>Licencia (solo chofer)</label><input type="text" name="personas[<?= e($p['id']) ?>][licencia_categoria]" value="<?= e($p['licencia_categoria']) ?>" class="w-full border rounded p-2"></div>
        </div>
      <?php endforeach; ?>
    </section>

    <!-- Vehículo -->
    <section>
      <h2 class="text-xl font-bold text-[#891628] mb-4">🚐 Vehículo</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div><label>Dominio</label><input type="text" name="vehiculo_dominio" value="<?= e($vehiculo['dominio'] ?? '') ?>" class="w-full border rounded p-2 uppercase" oninput="this.value=this.value.toUpperCase()"></div>
        <div><label>Marca</label><input type="text" name="vehiculo_marca" value="<?= e($vehiculo['marca'] ?? '') ?>" class="w-full border rounded p-2"></div>
        <div><label>Modelo</label><input type="text" name="vehiculo_modelo" value="<?= e($vehiculo['modelo'] ?? '') ?>" class="w-full border rounded p-2"></div>
        <div><label>Motor</label><input type="text" name="vehiculo_motor" value="<?= e($vehiculo['motor'] ?? '') ?>" class="w-full border rounded p-2"></div>
        <div><label>Asientos</label><input type="number" name="vehiculo_asientos" value="<?= e($vehiculo['asientos'] ?? '') ?>" class="w-full border rounded p-2"></div>
        <div><label>Inscripción Inicial</label><input type="date" name="vehiculo_inscripcion_inicial" value="<?= e($vehiculo['inscripcion_inicial'] ?? '') ?>" class="w-full border rounded p-2"></div>
      </div>
    </section>

    <!-- Establecimiento -->
    <section>
      <h2 class="text-xl font-bold text-[#891628] mb-4"><?= $data['tipo_transporte'] === 'Remis' ? '🚕 Remisería' : '🏫 Establecimiento' ?></h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div><label>Nombre</label><input type="text" name="establecimiento_nombre" value="<?= e($establecimiento['nombre'] ?? '') ?>" class="w-full border rounded p-2"></div>
        <div><label>Domicilio</label><input type="text" name="establecimiento_domicilio" value="<?= e($establecimiento['domicilio'] ?? '') ?>" class="w-full border rounded p-2"></div>
      </div>
    </section>

    <div class="text-center">
      <button type="submit" class="bg-[#891628] hover:bg-red-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition">
        💾 Guardar cambios
      </button>
    </div>

  </form>
</main>

</body>
</html>
