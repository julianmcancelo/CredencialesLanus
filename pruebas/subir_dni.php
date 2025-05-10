<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Leer DNI (Frente y Dorso)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4"></script>
</head>
<body class="bg-gray-100 p-6 min-h-screen">

<div class="max-w-4xl mx-auto bg-white rounded-xl shadow p-6 space-y-6">
  <h1 class="text-2xl font-bold text-[#891628] text-center">🆔 Leer DNI (Frente y Dorso)</h1>

  <form id="formularioDNI" class="space-y-4" onsubmit="return false;">
    <div>
      <label class="block mb-2 font-semibold text-gray-700">Subir Frente del DNI:</label>
      <input type="file" id="frente" accept="image/*,.pdf" required class="w-full border rounded px-3 py-2">
    </div>

    <div>
      <label class="block mb-2 font-semibold text-gray-700">Subir Dorso del DNI (opcional):</label>
      <input type="file" id="dorso" accept="image/*,.pdf" class="w-full border rounded px-3 py-2">
    </div>

    <button onclick="leerDNI()" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded">
      🔍 Procesar
    </button>
  </form>

  <div id="resultado" class="hidden mt-6 space-y-4">
    <h2 class="text-xl font-bold text-[#891628]">👁️ Datos detectados</h2>
    <form method="POST" action="guardar_persona.php" class="space-y-4">

      <div>
        <label class="block text-sm font-semibold">Nombre completo:</label>
        <input type="text" name="nombre" id="nombre" class="w-full border rounded px-3 py-2" required>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold">DNI:</label>
          <input type="text" name="dni" id="dni" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-semibold">Sexo:</label>
          <input type="text" name="sexo" id="sexo" class="w-full border rounded px-3 py-2" required>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold">Fecha de nacimiento:</label>
          <input type="text" name="fecha_nacimiento" id="fecha_nacimiento" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-semibold">Fecha de vencimiento:</label>
          <input type="text" name="fecha_vencimiento" id="fecha_vencimiento" class="w-full border rounded px-3 py-2" required>
        </div>
      </div>

      <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
        ✅ Confirmar
      </button>

    </form>
  </div>
</div>

<script>
async function leerDNI() {
  const frenteInput = document.getElementById('frente');
  const dorsoInput = document.getElementById('dorso');

  if (!frenteInput.files.length) {
    Swal.fire('Error', 'Debés subir al menos el frente del DNI.', 'error');
    return;
  }

  Swal.fire({
    title: 'Procesando...',
    html: 'Estamos leyendo la imagen, por favor esperá...',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    let texto = '';

    const frenteText = await procesarImagen(frenteInput.files[0]);
    texto += frenteText.toUpperCase() + '\n';

    if (dorsoInput.files.length) {
      const dorsoText = await procesarImagen(dorsoInput.files[0]);
      texto += dorsoText.toUpperCase();
    }

    console.log('Texto OCR detectado:', texto); // Para debug
    mostrarPreviewOCR(texto);
    completarDatos(texto);

    Swal.close();
  } catch (error) {
    console.error(error);
    Swal.fire('Error', 'Ocurrió un problema leyendo el archivo.', 'error');
  }
}

async function procesarImagen(file) {
  const { data: { text } } = await Tesseract.recognize(
    file,
    'spa', // Español
    {
      logger: m => console.log(m) // Ver progreso en consola
    }
  );
  return text;
}

function mostrarPreviewOCR(texto) {
  let preview = document.getElementById('previewOCR');
  if (!preview) {
    const div = document.createElement('div');
    div.id = 'previewOCR';
    div.className = 'mt-6 p-4 border rounded bg-gray-50 text-xs overflow-auto max-h-60';
    div.innerHTML = `<strong class="text-[#891628]">Texto leído OCR:</strong><br><pre>${texto}</pre>`;
    document.getElementById('resultado').insertBefore(div, document.getElementById('resultado').firstChild);
  } else {
    preview.innerHTML = `<strong class="text-[#891628]">Texto leído OCR:</strong><br><pre>${texto}</pre>`;
  }
}

function completarDatos(texto) {
  texto = texto.toUpperCase().replace(/\s\s+/g, ' ');

  // Buscar Apellido
  let nombreCompleto = '';
  const apellidoMatch = texto.match(/APELLIDO\/? SURNAME\s+([A-Z\s]+)/);
  if (apellidoMatch) {
    const apellido = apellidoMatch[1].trim();
    const nombreMatch = texto.match(/NOMBRE\/? NAME\s+([A-Z\s]+)/);
    const nombre = nombreMatch ? nombreMatch[1].trim() : '';
    nombreCompleto = `${apellido} ${nombre}`;
  }

  // Buscar DNI (documento)
  let dni = '';
  const dniMatch = texto.match(/DOCUMENTO\/? DOCUMENT\s+(\d{1,3}\.?\d{3}\.?\d{3})/);
  if (dniMatch) {
    dni = dniMatch[1].replace(/\./g, ''); // Quitar puntos
  }

  // Buscar Sexo
  let sexo = '';
  const sexoMatch = texto.match(/SEXO\/? SEX\s+([MF])/);
  if (sexoMatch) {
    sexo = sexoMatch[1];
  }

  // Buscar Fecha Nacimiento
  let fechaNacimiento = '';
  const nacimientoMatch = texto.match(/FECHA DE NACIMIENTO\/? DATE OF BIRTH\s+(\d{2}\s\w+\s\d{4})/);
  if (nacimientoMatch) {
    fechaNacimiento = corregirFechaTexto(nacimientoMatch[1]);
  }

  // Buscar Fecha Vencimiento
  let fechaVencimiento = '';
  const vencimientoMatch = texto.match(/FECHA DE VENCIMIENTO\/? DATE OF EXPIRY\s+(\d{2}\s\w+\s\d{4})/);
  if (vencimientoMatch) {
    fechaVencimiento = corregirFechaTexto(vencimientoMatch[1]);
  }

  // Llenar campos
  document.getElementById('nombre').value = nombreCompleto;
  document.getElementById('dni').value = dni;
  document.getElementById('sexo').value = sexo;
  document.getElementById('fecha_nacimiento').value = fechaNacimiento;
  document.getElementById('fecha_vencimiento').value = fechaVencimiento;

  document.getElementById('resultado').classList.remove('hidden');
}

function corregirFechaTexto(fecha) {
  const meses = {
    'ENE': '01', 'FEB': '02', 'MAR': '03', 'ABR': '04',
    'MAY': '05', 'JUN': '06', 'JUL': '07', 'AGO': '08',
    'SEP': '09', 'SET': '09', 'OCT': '10', 'NOV': '11', 'DIC': '12'
  };
  const partes = fecha.split(' ');
  if (partes.length === 3) {
    const dia = partes[0].padStart(2, '0');
    const mes = meses[partes[1].substr(0, 3)] || '01';
    const anio = partes[2];
    return `${dia}/${mes}/${anio}`;
  }
  return '';
}

function parseFechaMRZ(fecha) {
  if (!fecha || fecha.length !== 6) return '';
  const year = parseInt(fecha.slice(0, 2), 10);
  const month = fecha.slice(2, 4);
  const day = fecha.slice(4, 6);
  const siglo = (year < 30) ? '20' : '19';
  return `${day}/${month}/${siglo}${year}`;
}

// Opcional si querés seguir manteniendo detecciones clásicas:
function detectarNombre(texto) {
  let match = texto.match(/APELLIDO[S]*:? ([A-Z\s]+)/);
  if (!match) match = texto.match(/NOMBRE[S]*:? ([A-Z\s]+)/);
  return match ? match[1].replace(/\s\s+/g, ' ').trim() : '';
}

function detectarDNI(texto) {
  let match = texto.match(/DNI[: ]?(\d{7,8})/);
  if (!match) match = texto.match(/N° (\d{7,8})/);
  return match ? match[1] : '';
}

function detectarSexo(texto) {
  let match = texto.match(/SEXO[:]? ?(M|F)/);
  return match ? match[1] : '';
}

function detectarFechaNacimiento(texto) {
  let match = texto.match(/FECHA NACIMIENTO[:]? (\d{2}\/\d{2}\/\d{4})/);
  return match ? match[1] : '';
}

function detectarFechaVencimiento(texto) {
  let match = texto.match(/FECHA VENCIMIENTO[:]? (\d{2}\/\d{2}\/\d{4})/);
  return match ? match[1] : '';
}
</script>

</body>
</html>
