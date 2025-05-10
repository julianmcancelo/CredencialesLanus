<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Consultar Título Digital - DNRPA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6 flex flex-col">

<div class="max-w-4xl mx-auto bg-white shadow-lg rounded-xl p-6 space-y-8">

  <h1 class="text-3xl font-bold text-[#891628] text-center">🔎 Consultar Título Digital (DNRPA)</h1>

  <form id="formConsulta" class="space-y-6" onsubmit="return consultarTitulo();">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Registro Seccional</label>
        <input type="text" id="registro" required placeholder="Ej: 1134" class="w-full border border-gray-300 rounded px-3 py-2 text-center">
      </div>
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Número de Trámite</label>
        <input type="text" id="tramite" required placeholder="Ej: 266279" class="w-full border border-gray-300 rounded px-3 py-2 text-center">
      </div>
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Código de Control Web</label>
        <input type="text" id="control" required placeholder="Ej: 6B854275A1" class="w-full border border-gray-300 rounded px-3 py-2 text-center uppercase">
      </div>
    </div>

    <div class="flex justify-center">
      <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-8 rounded-full text-lg">
        🔍 Consultar
      </button>
    </div>
  </form>

</div>

<script>
function consultarTitulo() {
  const registro = document.getElementById('registro').value.trim();
  const tramite = document.getElementById('tramite').value.trim();
  const control = document.getElementById('control').value.trim().toUpperCase();

  if (!registro || !tramite || !control) {
    Swal.fire('Error', 'Completá todos los campos correctamente.', 'error');
    return false;
  }

  const url = `https://www2.jus.gov.ar/dnrpa-site/#!/consultarTramite/${registro}/${tramite}/${control}/titulo`;

  Swal.fire({
    title: 'Éxito',
    text: 'Se abrirá el trámite en una nueva pestaña.',
    icon: 'success',
    confirmButtonText: 'Ver Título'
  }).then(() => {
    window.open(url, '_blank');
  });

  return false;
}
</script>

</body>
</html>
