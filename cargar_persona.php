<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';
session_start();

// Previene salida prematura
ob_start();

// Obtener ID y ROL
$habilitacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rol = isset($_GET['rol']) ? strtolower(trim($_GET['rol'] ?? '')) : 'titular';
$label = ucfirst($rol);

$roles_permitidos = ['titular', 'chofer', 'celador'];
if (!in_array($rol, $roles_permitidos)) {
    die("Rol no válido.");
}

// POST = Procesar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $persona_id = intval($_POST['persona_id'] ?? 0);
    $nueva_persona = trim($_POST['nueva_persona'] ?? '');
    $nuevo_dni = trim($_POST['nuevo_dni'] ?? '');
    $nuevo_cuit = trim($_POST['nuevo_cuit'] ?? '');
    $nueva_telefono = trim($_POST['nuevo_telefono'] ?? '');
    $nueva_email = trim($_POST['nuevo_email'] ?? '');
    $nueva_domicilio = trim($_POST['nuevo_domicilio'] ?? '');
    $foto_url = trim($_POST['foto_url'] ?? 'https://credenciales.transportelanus.com.ar/assets/sinfoto.png');
    $licencia_categoria = trim($_POST['licencia_categoria'] ?? null);

    $rol_formateado = ($rol === 'chofer') ? 'CONDUCTOR' : ucfirst($rol);

    try {
        $pdo->beginTransaction();

        if ($persona_id > 0) {
            // Si ya existe, actualizar email, domicilio y teléfono
            $stmt = $pdo->prepare("UPDATE personas SET email = ?, domicilio = ?, telefono = ?, foto_url = ? WHERE id = ?");
            $stmt->execute([
                $nueva_email ?: null,
                $nueva_domicilio ?: null,
                $nueva_telefono ?: null,
                $foto_url,
                $persona_id
            ]);
        } else {
            if (!$nuevo_dni || !$nueva_persona) {
                throw new Exception('Nombre y DNI son obligatorios.');
            }

            $stmt = $pdo->prepare("SELECT id FROM personas WHERE dni = ?");
            $stmt->execute([$nuevo_dni]);
            $persona_existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($persona_existente) {
                $persona_id = $persona_existente['id'];

                $stmt = $pdo->prepare("UPDATE personas SET email = ?, domicilio = ?, telefono = ?, foto_url = ? WHERE id = ?");
                $stmt->execute([
                    $nueva_email ?: null,
                    $nueva_domicilio ?: null,
                    $nueva_telefono ?: null,
                    $foto_url,
                    $persona_id
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO personas (nombre, dni, cuit, telefono, email, domicilio, foto_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $nueva_persona,
                    $nuevo_dni,
                    $nuevo_cuit ?: $nuevo_dni,
                    $nueva_telefono ?: null,
                    $nueva_email ?: null,
                    $nueva_domicilio ?: null,
                    $foto_url
                ]);
                $persona_id = $pdo->lastInsertId();
            }
        }

        // Asociar persona a habilitación
        $stmt = $pdo->prepare("INSERT INTO habilitaciones_personas (habilitacion_id, persona_id, rol, licencia_categoria) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $habilitacion_id,
            $persona_id,
            $rol_formateado,
            $licencia_categoria
        ]);

        $pdo->commit();

        ob_end_clean();
echo "
<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
<script>
window.onload = function() {
  Swal.fire({
    icon: 'success',
    title: 'Asignación exitosa',
    text: 'La persona fue asignada correctamente a la habilitación.',
    confirmButtonText: 'OK'
  }).then(() => {
    window.location.href = 'index.php?id=$habilitacion_id';
  });
};
</script>";
exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        ob_end_clean();
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
          Swal.fire({
            icon: 'error',
            title: 'Error',
            html: '".addslashes($e->getMessage())."',
            confirmButtonText: 'Aceptar'
          });
        </script>";
        exit;
    }
}

// Consultar habilitación
$stmt = $pdo->prepare("SELECT nro_licencia FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$habilitacion_id]);
$nro_licencia = $stmt->fetchColumn();

// Consultar personas
$personas = $pdo->query("SELECT id, nombre, dni, email, domicilio, telefono, foto_url FROM personas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asignar <?= $label ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-3xl bg-white p-8 rounded-2xl shadow-2xl">
    <div class="mb-8 text-center">
      <h2 class="text-3xl font-extrabold text-[#891628] mb-2">Asignar <?= $label ?></h2>
      <p class="text-gray-600 text-sm">Asignando a habilitación <span class="font-semibold text-gray-800">N° <?= htmlspecialchars($nro_licencia) ?></span> (ID <?= $habilitacion_id ?>).</p>
    </div>

    <form id="formularioAsignar" method="POST" class="space-y-6">
      <input type="hidden" name="habilitacion_id" value="<?= $habilitacion_id ?>">
      <input type="hidden" name="persona_id" id="persona_id">
      <input type="hidden" name="foto_url" id="foto_url" value="https://credenciales.transportelanus.com.ar/assets/sinfoto.png">
      <input type="hidden" name="confirmado" value="1">

      <div class="relative">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Buscar Persona</label>
        <input type="text" id="buscar" autocomplete="off" placeholder="Escribí nombre o DNI..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-400">
        <ul id="sugerencias" class="absolute w-full bg-white border border-gray-300 rounded-lg shadow mt-1 hidden max-h-40 overflow-y-auto z-50"></ul>
      </div>

      <div id="nuevaPersona" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre Completo</label>
          <input type="text" name="nueva_persona" id="nueva_nombre" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">DNI</label>
          <input type="text" name="nuevo_dni" id="nueva_dni" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">CUIT</label>
          <input type="text" name="nuevo_cuit" id="nueva_cuit" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
          <input type="text" name="nuevo_telefono" id="nueva_telefono" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
          <input type="email" name="nuevo_email" id="nueva_email" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Domicilio</label>
          <input type="text" name="nuevo_domicilio" id="nueva_domicilio" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
      </div>

      <?php if ($rol === 'chofer'): ?>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Categoría de Licencia</label>
        <input type="text" name="licencia_categoria" placeholder="Ej: D1, D2" class="w-full border border-gray-300 rounded-lg px-4 py-2">
      </div>
      <?php endif; ?>

      <div class="text-center">
        <button type="button" onclick="confirmarAsignacion()" class="bg-[#891628] hover:bg-red-800 text-white font-bold py-2 px-6 rounded-lg shadow-md transition">✅ Asignar <?= $label ?></button>
      </div>
    </form>

    <div class="text-center mt-6">
      <a href="index.php" class="text-blue-600 hover:underline text-sm">&larr; Volver al Panel</a>
    </div>
  </div>

<script>
const personas = <?= json_encode($personas) ?>;
const buscar = document.getElementById('buscar');
const sugerencias = document.getElementById('sugerencias');
const personaInput = document.getElementById('persona_id');
const nuevaPersonaDiv = document.getElementById('nuevaPersona');
const campos = {
  nombre: document.getElementById('nueva_nombre'),
  dni: document.getElementById('nueva_dni'),
  cuit: document.getElementById('nueva_cuit'),
  telefono: document.getElementById('nueva_telefono'),
  email: document.getElementById('nueva_email'),
  domicilio: document.getElementById('nueva_domicilio'),
};
const fotoUrlInput = document.getElementById('foto_url');

buscar.addEventListener('input', () => {
  const filtro = buscar.value.toLowerCase();
  sugerencias.innerHTML = '';

  if (filtro.length < 2) {
    sugerencias.classList.add('hidden');
    return;
  }

  const resultados = personas.filter(p => 
    p.nombre.toLowerCase().includes(filtro) || (p.dni && p.dni.toString().includes(filtro))
  );

  if (resultados.length) {
    sugerencias.classList.remove('hidden');
    resultados.forEach(p => {
      const li = document.createElement('li');
      li.className = 'px-4 py-2 hover:bg-red-100 cursor-pointer';
      li.textContent = `${p.nombre} - DNI ${p.dni}`;
      li.onclick = () => {
        buscar.value = `${p.nombre} - DNI ${p.dni}`;
        personaInput.value = p.id;
        sugerencias.classList.add('hidden');

        // AUTO-RELLENAR datos
        campos.nombre.value = p.nombre;
        campos.dni.value = p.dni;
        campos.cuit.value = p.cuit;
        campos.telefono.value = p.telefono;
        campos.email.value = p.email;
        campos.domicilio.value = p.domicilio;

        // BLOQUEAR nombre, dni, cuit, telefono
        campos.nombre.readOnly = true;
        campos.dni.readOnly = true;
        campos.cuit.readOnly = true;
        campos.telefono.readOnly = true;

        // DESBLOQUEAR email y domicilio
        campos.email.readOnly = false;
        campos.domicilio.readOnly = false;

        nuevaPersonaDiv.classList.remove('hidden');

        // Establecer foto si tiene
        fotoUrlInput.value = p.foto_url || "https://credenciales.transportelanus.com.ar/assets/sinfoto.png";
      };
      sugerencias.appendChild(li);
    });
  } else {
    sugerencias.classList.add('hidden');
    personaInput.value = '';

    // HABILITAR todo en blanco para nueva persona
    nuevaPersonaDiv.classList.remove('hidden');
    for (const campo in campos) {
      campos[campo].value = '';
      campos[campo].readOnly = false;
    }
    fotoUrlInput.value = "https://credenciales.transportelanus.com.ar/assets/sinfoto.png";
  }
});

document.addEventListener('click', (e) => {
  if (!sugerencias.contains(e.target) && e.target !== buscar) {
    sugerencias.classList.add('hidden');
  }
});

function confirmarAsignacion() {
  const nombre = campos.nombre.value || buscar.value;
  const dni = campos.dni.value;
  const cuit = campos.cuit.value;
  const telefono = campos.telefono.value;
  const email = campos.email.value;
  const domicilio = campos.domicilio.value;
  const foto = fotoUrlInput.value;

  Swal.fire({
    title: '¿Confirmar asignación?',
    html: `
      <div class="text-left text-sm space-y-2">
        <img src="${foto}" alt="Foto" class="rounded-full w-24 h-24 mx-auto mb-4 border shadow">
        <p><strong>Nombre:</strong> ${nombre}</p>
        <p><strong>DNI:</strong> ${dni}</p>
        <p><strong>CUIT:</strong> ${cuit}</p>
        <p><strong>Teléfono:</strong> ${telefono}</p>
        <p><strong>Email:</strong> ${email}</p>
        <p><strong>Domicilio:</strong> ${domicilio}</p>
      </div>
    `,
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'Sí, asignar',
    cancelButtonText: 'Cancelar',
    customClass: { popup: 'rounded-xl' }
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('formularioAsignar').submit();
    }
  });
}
</script>

</body>
</html>
