<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Personas Asociadas - Paso 2</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto p-6 bg-white mt-10 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-blue-700">Paso 2: Personas Asociadas</h2>
    <form action="guardar_personas.php" method="POST">
      <input type="hidden" name="habilitacion_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">

      <!-- Titular -->
      <h3 class="text-lg font-semibold mb-2 text-gray-800">Titular</h3>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" name="titular_nombre" placeholder="Nombre del Titular" class="input" required>
        <input type="text" name="titular_dni" placeholder="DNI del Titular" class="input" required>
        <input type="text" name="titular_cuit" placeholder="CUIT del Titular" class="input">
        <input type="url" name="titular_foto_url" placeholder="URL de la Foto del Titular" class="input">
      </div>

      <!-- Conductor -->
      <h3 class="text-lg font-semibold mb-2 text-gray-800">Conductor</h3>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" name="conductor_nombre" placeholder="Nombre del Conductor" class="input">
        <input type="text" name="conductor_dni" placeholder="DNI del Conductor" class="input">
        <input type="text" name="conductor_licencia" placeholder="Licencia" class="input">
        <input type="url" name="conductor_foto_url" placeholder="URL Foto del Conductor" class="input">
      </div>

      <!-- Celadora -->
      <h3 class="text-lg font-semibold mb-2 text-gray-800">Celadora</h3>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" name="celador_nombre" placeholder="Nombre del Celador/a" class="input">
        <input type="text" name="celador_dni" placeholder="DNI del Celador/a" class="input">
      </div>

      <div class="text-center">
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded">Guardar y Continuar</button>
      </div>
    </form>
  </div>

  <style>
    .input {
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 0.375rem;
      width: 100%;
    }
  </style>
</body>
</html>
