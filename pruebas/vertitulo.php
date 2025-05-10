<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Consultar Título Digital - DNRPA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<div class="max-w-5xl mx-auto bg-white shadow-lg rounded-xl p-8 space-y-8 mt-6">

  <h1 class="text-4xl font-bold text-[#891628] text-center">🔎 Consultar Título Digital (DNRPA)</h1>

  <form id="formConsulta" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Registro Seccional</label>
        <input type="text" id="registro" name="registro" required placeholder="Ej: 1134" class="w-full border border-gray-300 rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Número de Trámite</label>
        <input type="text" id="tramite" name="tramite" required placeholder="Ej: 266279" class="w-full border border-gray-300 rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Número de Control Web</label>
        <input type="text" id="control" name="control" required placeholder="Ej: 6B854275A1" class="w-full border border-gray-300 rounded px-3 py-2 uppercase">
      </div>
    </div>

    <button type="button" onclick="consultarTitulo()" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded text-lg">
      🔍 Consultar
    </button>
  </form>

  <div id="visorPDF" class="hidden space-y-6 mt-10">
    <h2 class="text-2xl font-bold text-[#891628]">📄 Vista previa del Título Digital</h2>
    <div class="relative w-full" style="height: 90vh;">
      <iframe id="iframeTitulo" class="w-full h-full border rounded-lg shadow" frameborder="0"></iframe>
    </div>

    <a id="btnDescargar" href="#" target="_blank" class="block bg-blue-600 hover:bg-blue-700 text-white text-center font-semibold py-3 rounded text-lg">
      ⬇️ Abrir en Nueva Pestaña
    </a>
  </div>

</div>

<script>
function consultarTitulo() {
  const registro = document.getElementById('registro').value.trim();
  const tramite = document.getElementById('tramite').value.trim();
  const control = document.getElementById('control').value.trim().toUpperCase();

  if (!registro || !tramite || !control) {
    Swal.fire('Error', 'Completá todos los campos correctamente.', 'error');
    return;
  }

  const url = `https://www2.jus.gov.ar/dnrpa-site/#!/consultarTramite/${registro}/${tramite}/${control}/titulo`;

  document.getElementById('iframeTitulo').src = url;
  document.getElementById('btnDescargar').href = url;
  document.getElementById('visorPDF').classList.remove('hidden');
}
</script>

</body>
</html>
