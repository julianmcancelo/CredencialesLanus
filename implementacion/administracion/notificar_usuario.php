<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  header('Location: ../login.php');
  exit;
}

// Traer personas con tipo de transporte
$stmt = $pdo->query("
  SELECT DISTINCT p.id, p.nombre, p.email, hg.tipo_transporte
  FROM personas p
  JOIN habilitaciones_personas hp ON hp.persona_id = p.id
  JOIN habilitaciones_generales hg ON hg.id = hp.habilitacion_id
  WHERE p.email IS NOT NULL AND p.email != ''
  ORDER BY p.nombre ASC
");
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extraer tipos únicos
$tipos = array_unique(array_filter(array_column($personas, 'tipo_transporte')));
sort($tipos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Notificar Personas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function filtrarPorTipo() {
      const tipoSeleccionado = document.getElementById('tipo_transporte').value;
      const opciones = document.querySelectorAll('#persona_ids option');

      opciones.forEach(opt => {
        const tipo = opt.getAttribute('data-tipo');
        opt.hidden = tipoSeleccionado && tipo !== tipoSeleccionado;
      });
    }

    function seleccionarTodos() {
      document.querySelectorAll('#persona_ids option').forEach(opt => opt.selected = true);
    }

    function deseleccionarTodos() {
      document.querySelectorAll('#persona_ids option').forEach(opt => opt.selected = false);
    }

    function mostrarSpinner() {
      document.getElementById('btnText').textContent = 'Enviando...';
      document.getElementById('spinner').classList.remove('hidden');
    }
  </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="w-full max-w-2xl bg-white p-8 rounded-lg shadow-lg">
    <h2 class="text-3xl font-bold text-[#891628] mb-6 text-center">📢 Notificar Personas</h2>

    <form action="enviar_notificacion.php" method="POST" class="space-y-6" onsubmit="mostrarSpinner()">
      <div>
        <label for="tipo_transporte" class="font-semibold block mb-2">Filtrar por tipo de transporte:</label>
        <select id="tipo_transporte" onchange="filtrarPorTipo()" class="w-full p-3 border rounded shadow-sm">
          <option value="">-- Todos --</option>
          <?php foreach ($tipos as $tipo): ?>
            <option value="<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($tipo) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="font-semibold block mb-2">Seleccionar personas:</label>
        <div class="flex justify-between mb-1">
          <button type="button" onclick="seleccionarTodos()" class="text-sm text-blue-600 hover:underline">✅ Seleccionar todos</button>
          <button type="button" onclick="deseleccionarTodos()" class="text-sm text-red-600 hover:underline">❌ Deseleccionar todos</button>
        </div>
        <select name="persona_ids[]" id="persona_ids" multiple size="10" class="w-full p-3 border rounded bg-white shadow-inner h-64">
          <?php foreach ($personas as $p): ?>
            <option value="<?= $p['id'] ?>" data-tipo="<?= htmlspecialchars($p['tipo_transporte']) ?>">
              <?= htmlspecialchars($p['nombre']) ?> (<?= $p['email'] ?>) [<?= $p['tipo_transporte'] ?>]
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-sm text-gray-500 mt-1">Usá Ctrl o Shift para selección múltiple.</p>
      </div>

      <div>
        <label for="mensaje" class="font-semibold block mb-2">Mensaje:</label>
        <textarea name="mensaje" id="mensaje" rows="6" class="w-full p-3 border rounded shadow-sm" placeholder="Escribí tu mensaje..." required></textarea>
      </div>

      <button type="submit" id="botonEnviar" class="w-full flex justify-center items-center gap-3 bg-[#891628] text-white py-3 px-6 rounded hover:bg-[#6e1020] transition">
        <span id="btnText">Enviar Notificación</span>
        <svg id="spinner" class="hidden w-5 h-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l5-5-5-5v4a10 10 0 00-10 10h4z"></path>
        </svg>
      </button>
    </form>
  </div>
</body>
</html>
