<?php
require_once '../conexion.php';
session_start();
$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Administrador';

$mensaje = $_GET['mensaje'] ?? null;

$stmt = $pdo->query("SELECT * FROM personas ORDER BY id DESC");
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listado de Personas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <!-- Encabezado con nombre de usuario -->
  <header class="bg-blue-800 text-white p-4 shadow">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
      <h1 class="text-lg font-bold">Panel de Administración</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm">👤 <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="logout.php" class="text-sm underline hover:text-gray-200">Cerrar sesión</a>
      </div>
    </div>
  </header>

  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4 text-center text-blue-700">Listado de Personas Registradas</h1>

    <?php if ($mensaje): ?>
      <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-2 mb-4 rounded">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
      <table class="min-w-full table-auto text-sm">
        <thead class="bg-gray-200 text-gray-700 uppercase">
          <tr>
            <th class="px-4 py-2">ID</th>
            <th class="px-4 py-2">Nombre</th>
            <th class="px-4 py-2">DNI</th>
            <th class="px-4 py-2">CUIT</th>
            <th class="px-4 py-2">Teléfono</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">Foto</th>
            <th class="px-4 py-2">Editar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($personas as $p): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-2"><?php echo $p['id']; ?></td>
            <td class="px-4 py-2"><?php echo htmlspecialchars($p['nombre']); ?></td>
            <td class="px-4 py-2"><?php echo $p['dni']; ?></td>
            <td class="px-4 py-2"><?php echo $p['cuit']; ?></td>
            <td class="px-4 py-2"><?php echo $p['telefono']; ?></td>
            <td class="px-4 py-2"><?php echo $p['email']; ?></td>
            <td class="px-4 py-2">
              <?php if ($p['foto_url']): ?>
                <img src="<?php echo $p['foto_url']; ?>" alt="Foto" class="w-10 h-10 rounded-full object-cover border">
              <?php else: ?>
                <span class="text-gray-400">-</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-2">
              <button onclick="abrirModalEditar(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['nombre'])); ?>', '<?php echo $p['dni']; ?>', '<?php echo $p['cuit']; ?>', '<?php echo $p['telefono']; ?>', '<?php echo $p['email']; ?>')" class="text-yellow-600 hover:underline">Editar</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-center mt-4">
      <a href="index.php" class="text-sm text-blue-700 hover:underline">← Volver al Panel</a>
    </div>
  </div>

  <!-- Modal de edición -->
  <div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
      <button onclick="cerrarModalEditar()" class="absolute top-2 right-2 text-gray-600 hover:text-red-600 text-xl">×</button>
      <h2 class="text-lg font-bold text-blue-700 mb-4">Editar Persona</h2>
      <form action="guardar_edicion_persona.php" method="POST" class="space-y-4">
        <input type="hidden" name="id" id="edit-id">
        <div>
          <label class="block text-sm font-medium">Nombre</label>
          <input type="text" name="nombre" id="edit-nombre" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">DNI</label>
          <input type="text" name="dni" id="edit-dni" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">CUIT</label>
          <input type="text" name="cuit" id="edit-cuit" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Teléfono</label>
          <input type="text" name="telefono" id="edit-telefono" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input type="email" name="email" id="edit-email" class="w-full mt-1 border rounded px-3 py-2">
        </div>
        <div class="text-right">
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function abrirModalEditar(id, nombre, dni, cuit, telefono, email) {
      document.getElementById('edit-id').value = id;
      document.getElementById('edit-nombre').value = nombre;
      document.getElementById('edit-dni').value = dni;
      document.getElementById('edit-cuit').value = cuit;
      document.getElementById('edit-telefono').value = telefono;
      document.getElementById('edit-email').value = email;
      document.getElementById('modalEditar').classList.remove('hidden');
    }

    function cerrarModalEditar() {
      document.getElementById('modalEditar').classList.add('hidden');
    }
  </script>
</body>
</html>
