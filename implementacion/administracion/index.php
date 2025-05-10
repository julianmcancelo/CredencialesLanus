<?php
// ACTIVAR ERRORES Y VALIDAR SESIÓN ADMIN
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  header('Location: ../login.php');
  exit;
}

$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Administrador';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Panel de Administración</title>
  <meta name="description" content="Panel de administración del sistema de habilitaciones Lanús" />
  <link rel="icon" href="../favicon.ico" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .animate-fadeIn {
      animation: fadeIn 0.25s ease-out both;
    }
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(15px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

  <!-- ENCABEZADO -->
  <header class="bg-[#891628] text-white p-4 shadow">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
      <h1 class="text-xl font-bold">🛠 Panel de Administración</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm">👤 <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="logout.php" class="text-sm underline hover:text-gray-200">Cerrar sesión</a>
      </div>
    </div>
  </header>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="max-w-6xl mx-auto mt-6 px-4 grow">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h2 class="text-xl font-bold text-[#891628] mb-4">🗂 Accesos rápidos</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $secciones = [
          ['id' => 'modalPersonas',       'label' => '👤 Personas',              'color' => 'blue'],
          ['id' => 'modalVehiculos',      'label' => '🚐 Vehículos',             'color' => 'purple'],
          ['id' => 'modalEstablecimientos','label'=> '🏫 Establecimientos',     'color' => 'yellow'],
          ['id' => 'modalHabilitaciones', 'label' => '📄 Habilitaciones',        'color' => 'green'],
          ['id' => 'modalVerificacion',   'label' => '🔍 Verificación Vehicular','color' => 'red'],
          ['id' => 'modalReportes',       'label' => '📈 Reportes y estadísticas','color' => 'teal'],
          ['id' => 'modalNotificaciones', 'label' => '✉️ Notificaciones',        'color' => 'indigo'],
          ['id' => 'modalSeguridad',      'label' => '🛡 Seguridad y roles',     'color' => 'gray'],
        ];

        foreach ($secciones as $sec) {
          echo "<button onclick=\"abrirModal('{$sec['id']}')\" class=\"bg-{$sec['color']}-600 hover:bg-{$sec['color']}-700 text-white px-4 py-2 rounded shadow transition-all\">{$sec['label']}</button>";
        }
        ?>
      </div>
    </div>
  </main>

  <!-- PIE DE PÁGINA -->
  <footer class="bg-white text-center text-gray-500 text-sm p-4 shadow-inner">
    © <?= date('Y') ?> Municipio de Lanús — Sistema de Gestión de Transporte
  </footer>

  <!-- MODALES -->
  <?php
  $modales = [
    'Personas' => [
      'title' => '👤 Gestión de Personas',
      'enlaces' => [
        ['ruta' => 'registro_persona.php', 'texto' => '➕ Registrar nueva persona'],
        ['ruta' => 'listado_personas.php', 'texto' => '📋 Ver listado'],
      ],
      'color' => 'text-blue-700',
    ],
    'Vehiculos' => [
      'title' => '🚐 Gestión de Vehículos',
      'enlaces' => [
        ['ruta' => 'registro_vehiculo.php', 'texto' => '➕ Registrar nuevo vehículo'],
        ['ruta' => 'listado_vehiculos.php', 'texto' => '📋 Ver listado'],
      ],
      'color' => 'text-purple-700',
    ],
    'Establecimientos' => [
      'title' => '🏫 Gestión de Establecimientos',
      'enlaces' => [
        ['ruta' => 'registro_establecimiento.php', 'texto' => '➕ Registrar nuevo establecimiento'],
        ['ruta' => 'listado_establecimientos.php', 'texto' => '📋 Ver listado'],
      ],
      'color' => 'text-yellow-700',
    ],
    'Habilitaciones' => [
      'title' => '📄 Gestión de Habilitaciones',
      'enlaces' => [
        ['ruta' => 'registro_habilitacion.php', 'texto' => '➕ Registrar nueva habilitación'],
        ['ruta' => 'panel.php', 'texto' => '📋 Ver habilitaciones'],
      ],
      'color' => 'text-green-700',
    ],
    'Verificacion' => [
      'title' => '🔍 Verificación Vehicular',
      'enlaces' => [
        ['ruta' => 'verificacion_listado.php', 'texto' => '📋 Certificados generados'],
        ['ruta' => 'verificacion_manual.php', 'texto' => '📝 Generar certificado manual'],
      ],
      'color' => 'text-red-700',
    ],
    'Reportes' => [
      'title' => '📈 Reportes del sistema',
      'enlaces' => [
        ['ruta' => 'reporte_diario.php', 'texto' => '📆 Reporte diario'],
        ['ruta' => 'reporte_excel.php', 'texto' => '📥 Descargar Excel'],
      ],
      'color' => 'text-teal-700',
    ],
    'Notificaciones' => [
      'title' => '✉️ Centro de Notificaciones',
      'enlaces' => [
        ['ruta' => 'notificar_usuario.php', 'texto' => '📨 Notificar usuario'],
        ['ruta' => 'notificar_grupo.php', 'texto' => '📨 Notificar a grupo'],
      ],
      'color' => 'text-indigo-700',
    ],
    'Seguridad' => [
      'title' => '🛡 Control de Seguridad',
      'enlaces' => [
        ['ruta' => 'usuarios_admin.php', 'texto' => '👤 Gestionar administradores'],
        ['ruta' => 'log_accesos.php', 'texto' => '📜 Ver accesos'],
      ],
      'color' => 'text-gray-700',
    ],
  ];

  foreach ($modales as $id => $data) {
    echo "<div id='modal$id' class='fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center'>";
    echo "<div class='bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 relative animate-fadeIn'>";
    echo "<button onclick=\"cerrarModal('modal$id')\" class='absolute top-3 right-3 text-gray-500 hover:text-red-600 text-2xl font-bold'>&times;</button>";
    echo "<h2 class='text-2xl font-semibold text-[#891628] mb-4'>{$data['title']}</h2>";
    echo "<div class='space-y-3 text-gray-700 text-lg'>";
    foreach ($data['enlaces'] as $link) {
      echo "<a href='{$link['ruta']}' class='block {$data['color']} hover:underline hover:font-medium transition'>{$link['texto']}</a>";
    }
    echo "</div></div></div>";
  }
  ?>

  <!-- JAVASCRIPT FUNCIONAL -->
  <script>
    function abrirModal(id) {
      const modal = document.getElementById(id);
      if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }
    }

    function cerrarModal(id) {
      const modal = document.getElementById(id);
      if (modal) {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
      }
    }

    // ESC para cerrar
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('[id^=modal]').forEach(modal => {
          modal.classList.remove('flex');
          modal.classList.add('hidden');
        });
      }
    });

    // Clic afuera del contenido para cerrar
    document.querySelectorAll('[id^=modal]').forEach(modal => {
      modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrarModal(modal.id);
      });
    });
  </script>

</body>
</html>
