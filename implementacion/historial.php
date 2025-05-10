<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Historial de Habilitaciones</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="bg-[#891628] text-white p-4 flex justify-between items-center shadow-lg">
    <h1 class="text-xl font-bold animate__animated animate__fadeInLeft">Historial de Habilitaciones</h1>
    <a href="panel.php" class="text-sm underline hover:text-gray-200 animate__animated animate__fadeInRight">← Volver al panel</a>
  </div>

  <div class="max-w-5xl mx-auto mt-6 px-4">
    <div class="bg-white rounded-xl shadow-xl p-6 animate__animated animate__fadeInUp">
      <h2 class="text-2xl font-bold text-[#891628] mb-2">Versiones Anteriores</h2>
      <p class="text-sm text-gray-600 mb-6">Visualizá todas las versiones históricas de una misma habilitación por año. Cada una mantiene su token y credencial independiente.</p>

      <div class="overflow-x-auto rounded border border-gray-200">
        <table class="min-w-full text-sm divide-y divide-gray-200">
          <thead class="bg-[#fcebea] text-[#891628] text-xs uppercase">
            <tr>
              <th class="px-4 py-2 text-left">Año</th>
              <th class="px-4 py-2 text-left">Licencia</th>
              <th class="px-4 py-2 text-left">Titular</th>
              <th class="px-4 py-2 text-left">Estado</th>
              <th class="px-4 py-2 text-left">Token</th>
              <th class="px-4 py-2 text-left">Ver</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php
            require_once 'conexion.php';
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            $stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE nro_licencia = (SELECT nro_licencia FROM habilitaciones_generales WHERE id = ?) ORDER BY anio DESC");
            $stmt->execute([$id]);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($historial as $item):
              $stmt2 = $pdo->prepare("SELECT p.nombre FROM habilitaciones_personas hp JOIN personas p ON p.id = hp.persona_id WHERE hp.habilitacion_id = ? AND hp.rol = 'TITULAR'");
              $stmt2->execute([$item['id']]);
              $titular = $stmt2->fetchColumn();

              $stmt3 = $pdo->prepare("SELECT token FROM tokens_acceso WHERE habilitacion_id = ? ORDER BY fecha_expiracion DESC LIMIT 1");
              $stmt3->execute([$item['id']]);
              $token = $stmt3->fetchColumn();
            ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 font-semibold text-gray-800"><?= $item['anio'] ?></td>
                <td class="px-4 py-2 text-gray-700 whitespace-nowrap"><?= $item['nro_licencia'] ?></td>
                <td class="px-4 py-2 text-gray-700 whitespace-nowrap"><?= htmlspecialchars($titular) ?></td>
                <td class="px-4 py-2">
                  <span class="px-2 py-1 text-xs rounded-full font-semibold <?= $item['estado'] === 'HABILITADO' ? 'bg-green-100 text-green-700' : ($item['estado'] === 'EN TRAMITE' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                    <?= $item['estado'] ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-xs text-gray-500 font-mono max-w-xs truncate"><?= $token ?: '-' ?></td>
                <td class="px-4 py-2">
                  <?php if ($token): ?>
                    <a href="credencial.php?token=<?= $token ?>" target="_blank" class="text-blue-600 underline hover:text-blue-800 text-sm">Ver credencial</a>
                  <?php else: ?>
                    <span class="text-gray-400 text-sm">No disponible</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="text-xs text-gray-500 mt-6 text-center">
        Este historial es generado automáticamente por el sistema cada vez que se crea una nueva versión anual.<br>
        Para renovar una habilitación, hacé clic en el botón <strong class="text-[#891628]">"Renovar"</strong> desde el panel principal.
      </div>
    </div>
  </div>
</body>
</html>