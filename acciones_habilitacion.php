<a href="editar_habilitacion.php?id=<?= $item['habilitacion_id'] ?>"
   class="block w-full bg-yellow-500 hover:bg-yellow-600 text-white rounded px-3 py-1 shadow transition text-sm text-center">
  ✏️ Editar
</a>

<a href="enviar_correo.php?id=<?= $item['habilitacion_id'] ?>"
   class="block w-full bg-green-600 hover:bg-green-700 text-white rounded px-3 py-1 shadow transition text-sm text-center">
  📩 Enviar
</a>

<a href="credencial.php?token=<?= $token ?>"
   class="block w-full rounded px-3 py-1 shadow transition text-sm text-center
   <?= $tokenVencido ? 'bg-gray-300 text-gray-500 line-through cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 text-white' ?>"
   <?= $tokenVencido ? 'onclick="return false;"' : '' ?>>
  🔍 Ver
</a>

<button onclick="abrirModalTurno(<?= $item['habilitacion_id'] ?>, '<?= htmlspecialchars($item['nro_licencia']) ?>')"
   class="block w-full bg-yellow-600 hover:bg-yellow-700 text-white rounded px-3 py-1 shadow transition text-sm text-center">
  ✍️ Turno
</button>

<a href="descargar_certificado.php?id=<?= $item['habilitacion_id'] ?>" target="_blank"
   class="block w-full bg-purple-600 hover:bg-purple-700 text-white rounded px-3 py-1 shadow transition text-sm text-center">
  📄 PDF
</a>
