<?php
require_once 'conexion.php';
session_start();

// 👉 API de búsqueda de vehículo
if (isset($_GET['buscar'])) {
    $buscar = strtoupper(trim($_GET['buscar']));
    $stmt = $pdo->prepare("SELECT marca, modelo, inscripcion_inicial, asientos, motor FROM vehiculos WHERE dominio = ?");
    $stmt->execute([$buscar]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    if ($vehiculo) {
        echo json_encode(['encontrado' => true] + $vehiculo);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    exit;
}

// ✨ Parte normal (formulario)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  header('Location: login.php');
  exit;
}

$mensaje = null;
$habilitacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$habilitacion_id) die("ID de habilitación inválido.");

// Buscar habilitación
$stmt = $pdo->prepare("SELECT nro_licencia FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$habilitacion_id]);
$habilitacion = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$habilitacion) die("Habilitación no encontrada.");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dominio'])) {
    $dominio = strtoupper(trim($_POST['dominio']));
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $inscripcion_inicial = $_POST['inscripcion_inicial'] ?: null;
    $asientos = $_POST['asientos'] !== '' ? intval($_POST['asientos']) : null;
    $motor = trim($_POST['motor']) ?: null;

    if ($dominio) {
        $stmt = $pdo->prepare("SELECT id FROM vehiculos WHERE dominio = ?");
        $stmt->execute([$dominio]);
        $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehiculo) {
            // Insertar nuevo vehículo
            $stmt = $pdo->prepare("INSERT INTO vehiculos (dominio, marca, modelo, inscripcion_inicial, asientos, motor) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$dominio, $marca, $modelo, $inscripcion_inicial, $asientos, $motor]);
            $vehiculo_id = $pdo->lastInsertId();
        } else {
            $vehiculo_id = $vehiculo['id'];
        }

        // Asociar vehículo a habilitación
        $stmt = $pdo->prepare("INSERT INTO habilitaciones_vehiculos (habilitacion_id, vehiculo_id) VALUES (?, ?)");
        $stmt->execute([$habilitacion_id, $vehiculo_id]);

        $mensaje = "✅ Vehículo asociado correctamente.";
    } else {
        $mensaje = "❌ Debés ingresar un dominio válido.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asociar Vehículo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-[#00adee] text-white p-4 shadow">
  <div class="max-w-7xl mx-auto flex justify-between items-center">
    <h1 class="text-2xl font-bold">#LaCiudadQueNosMerecemos</h1>
    <a href="index.php" class="hover:underline">← Volver</a>
  </div>
</header>

<main class="max-w-4xl mx-auto mt-8 bg-white rounded-xl shadow p-6">
  <h2 class="text-3xl font-bold text-[#891628] mb-6 text-center">🚐 Asociar Vehículo a Habilitación</h2>

  <?php if ($mensaje): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Operación Exitosa',
        html: '<?= $mensaje ?>',
        confirmButtonText: 'OK'
      }).then(() => {
        window.location.href = "index.php";
      });
    </script>
  <?php endif; ?>

  <form method="POST" class="space-y-6" id="formVehiculo">
    <div>
      <label class="block mb-1 font-semibold text-gray-700">Habilitación</label>
      <input type="text" value="<?= htmlspecialchars($habilitacion['nro_licencia']) ?>" disabled class="w-full bg-gray-100 border border-gray-300 rounded-md p-2">
    </div>

    <div>
      <label class="block mb-1 font-semibold text-gray-700">Dominio</label>
      <input type="text" name="dominio" id="dominio" placeholder="Ej: ABC123" required class="w-full border border-gray-300 rounded-md p-2 uppercase" onkeyup="buscarVehiculo()">
      <small id="estadoVehiculo" class="text-gray-500 text-sm">Escribí el dominio para buscar o crear uno nuevo.</small>
    </div>

    <div id="datosVehiculo" class="hidden space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block mb-1 font-semibold text-gray-700">Marca</label>
          <input type="text" name="marca" id="marca" class="w-full border border-gray-300 rounded-md p-2" required>
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700">Modelo</label>
          <input type="text" name="modelo" id="modelo" class="w-full border border-gray-300 rounded-md p-2" required>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block mb-1 font-semibold text-gray-700">Fecha Inscripción Inicial</label>
          <input type="date" name="inscripcion_inicial" id="inscripcion_inicial" class="w-full border border-gray-300 rounded-md p-2">
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700">Cantidad de Asientos</label>
          <input type="number" name="asientos" id="asientos" min="0" class="w-full border border-gray-300 rounded-md p-2">
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700">Motor</label>
          <input type="text" name="motor" id="motor" class="w-full border border-gray-300 rounded-md p-2">
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-4 mt-6">
      <a href="index.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancelar</a>
      <button type="submit" class="px-6 py-2 bg-[#891628] hover:bg-red-800 text-white rounded font-semibold shadow">Guardar</button>
    </div>
  </form>
</main>

<script>
async function buscarVehiculo() {
  const dominio = document.getElementById('dominio').value.trim().toUpperCase();
  const estado = document.getElementById('estadoVehiculo');
  const datosVehiculo = document.getElementById('datosVehiculo');
  const marca = document.getElementById('marca');
  const modelo = document.getElementById('modelo');
  const inscripcion = document.getElementById('inscripcion_inicial');
  const asientos = document.getElementById('asientos');
  const motor = document.getElementById('motor');

  if (dominio.length >= 3) {
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('buscar', dominio);

      const response = await fetch(url.toString());
      const data = await response.json();

      if (data.encontrado) {
        estado.textContent = `✅ Vehículo encontrado.`;
        marca.value = data.marca;
        modelo.value = data.modelo;
        inscripcion.value = data.inscripcion_inicial || '';
        asientos.value = data.asientos || '';
        motor.value = data.motor || '';
      } else {
        estado.textContent = "🚗 Vehículo no encontrado. Podés cargarlo.";
        marca.value = "";
        modelo.value = "";
        inscripcion.value = "";
        asientos.value = "";
        motor.value = "";
      }

      datosVehiculo.classList.remove('hidden');
    } catch (error) {
      console.error(error);
    }
  } else {
    estado.textContent = "Escribí un dominio válido.";
    datosVehiculo.classList.add('hidden');
  }
}
</script>

</body>
</html>
