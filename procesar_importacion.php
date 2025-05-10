<?php
require_once 'conexion.php';
session_start();

if (!isset($_POST['personas']) || !is_array($_POST['personas'])) {
    die("No hay datos para importar.");
}

$personas = $_POST['personas'];
$fotoUrlDefault = "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png";

$insertadas = 0;
$omitidas = 0;

foreach ($personas as $persona) {
    $nombre = trim($persona['nombre']);
    $dniRaw = trim($persona['dni']);
    $domicilio = trim($persona['domicilio'] ?? '');
    $telefonoRaw = trim($persona['telefono']);
    $email = trim($persona['email']);

    // LIMPIAR datos
    $dni = preg_replace('/[^0-9]/', '', $dniRaw); // Solo números
    $telefono = preg_replace('/[^0-9]/', '', $telefonoRaw); // Solo números
    $cuit = $dni; // CUIT igual al DNI

    // Validaciones
    if (empty($nombre) || empty($dni) || !ctype_digit($dni)) {
        $omitidas++;
        continue;
    }

    // Verificar si ya existe el DNI
    $stmt = $pdo->prepare("SELECT id FROM personas WHERE dni = ?");
    $stmt->execute([$dni]);
    if ($stmt->fetch()) {
        $omitidas++;
        continue;
    }

    // Insertar nueva persona
    $stmt = $pdo->prepare("
        INSERT INTO personas (nombre, dni, cuit, telefono, email, foto_url, domicilio)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $nombre,
        $dni,
        $cuit,
        $telefono ?: null,
        $email ?: null,
        $fotoUrlDefault,
        $domicilio ?: null
    ]);

    $insertadas++;
}

// Mensaje final
echo "
<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
<script>
Swal.fire({
  title: 'Importación Finalizada',
  html: '✔️ Insertadas: <b>$insertadas</b><br>⚠️ Omitidas (DNI inválido o duplicado): <b>$omitidas</b>',
  icon: 'success',
  confirmButtonText: 'OK'
}).then(() => {
  window.location.href = 'personas.php';
});
</script>
";