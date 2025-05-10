<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Datos Generales - Paso 1</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-3xl mx-auto p-6 bg-white mt-10 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-blue-700">Paso 1: Datos Generales de Habilitación</h2>
    <form action="guardar_datos_generales.php" method="POST">
      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" name="nro_licencia" placeholder="Nº de Licencia (Ej: 068-0001/25)" class="input" required>
        <input type="text" name="tipo_habilitacion" placeholder="Tipo de Habilitación" class="input" required>
        <input type="text" name="resolucion" placeholder="Resolución" class="input">
        <input type="text" name="expediente" placeholder="Expediente" class="input">
        <input type="date" name="vigencia_inicio" class="input" required>
        <input type="date" name="vigencia_fin" class="input" required>
        <select name="estado" class="input" required>
          <option value="">Estado</option>
          <option value="HABILITADO">HABILITADO</option>
          <option value="NO HABILITADO">NO HABILITADO</option>
          <option value="EN TRAMITE">EN TRÁMITE</option>
          <option value="INICIADO">INICIADO</option>
        </select>
        <input type="text" name="contacto" placeholder="Teléfono de contacto" class="input">
        <input type="email" name="email" placeholder="Email" class="input">
        <textarea name="observacion" placeholder="Observaciones generales" class="input" rows="2"></textarea>
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
