<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Establecimiento y Vehículo - Paso 3</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto p-6 bg-white mt-10 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-blue-700">Paso 3: Establecimiento y Vehículo</h2>
    <form action="guardar_establecimiento_vehiculo.php" method="POST">
      <input type="hidden" name="habilitacion_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">

      <!-- Establecimiento Educativo -->
      <h3 class="text-lg font-semibold mb-2 text-gray-800">Establecimiento Educativo</h3>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" name="establecimiento_nombre" placeholder="Nombre del Establecimiento" class="input">
        <input type="text" name="establecimiento_domicilio" placeholder="Domicilio" class="input">
        <input type="text" name="establecimiento_localidad" placeholder="Localidad" class="input">
        <input type="text" name="establecimiento_latitud" placeholder="Latitud" class="input">
        <input type="text" name="establecimiento_longitud" placeholder="Longitud" class="input">
      </div>

      <!-- Datos del Vehículo -->
      <h3 class="text-lg font-semibold mb-2 text-gray-800">Datos del Vehículo</h3>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" name="marca" placeholder="Marca" class="input">
        <input type="text" name="modelo" placeholder="Modelo" class="input">
        <input type="text" name="motor" placeholder="Motor" class="input">
        <input type="number" name="asientos" placeholder="Cantidad de Asientos" class="input">
        <input type="number" name="anio" placeholder="Año" class="input">
        <input type="date" name="inscripcion_inicial" placeholder="Inscripción Inicial" class="input">
      </div>

      <div class="text-center">
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded">Finalizar Registro</button>
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
