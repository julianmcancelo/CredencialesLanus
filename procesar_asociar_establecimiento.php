<?php
session_start();
require_once 'conexion.php';

// Validar sesión
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// Obtener datos
$habilitacion_id = intval($_POST['habilitacion_id'] ?? 0);
$establecimiento_id = intval($_POST['establecimiento_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$domicilio = trim($_POST['domicilio'] ?? '');
$localidad = trim($_POST['localidad'] ?? '');
$latitud = trim($_POST['latitud'] ?? '');
$longitud = trim($_POST['longitud'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

if (!$habilitacion_id || !$nombre || !$domicilio) {
    $_SESSION['mensaje_error'] = "Faltan campos obligatorios.";
    header("Location: asociar_establecimiento.php?id=$habilitacion_id");
    exit;
}

// Detectar si es remis o establecimiento
$stmt = $pdo->prepare("SELECT tipo_transporte FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$habilitacion_id]);
$tipoTransporte = $stmt->fetchColumn();
$esRemis = strtoupper((string)$tipoTransporte) === 'REMIS';

try {
    if ($establecimiento_id > 0) {
        // Si ya existe, actualizar
        if ($esRemis) {
            $stmt = $pdo->prepare("UPDATE remiserias SET nombre = ?, direccion = ?, latitud = ?, longitud = ?, localidad = ? WHERE id = ?");
            $stmt->execute([$nombre, $domicilio, $latitud, $longitud, $localidad, $establecimiento_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE establecimientos SET nombre = ?, domicilio = ?, localidad = ?, latitud = ?, longitud = ?, direccion = ? WHERE id = ?");
            $stmt->execute([$nombre, $domicilio, $localidad, $latitud, $longitud, $direccion, $establecimiento_id]);
        }
    } else {
        // Nuevo registro
        if ($esRemis) {
            $stmt = $pdo->prepare("INSERT INTO remiserias (nombre, direccion, latitud, longitud, creado_en, localidad, nro_habilitacion, nro_expediente) VALUES (?, ?, ?, ?, NOW(), ?, '', '')");
            $stmt->execute([$nombre, $domicilio, $latitud, $longitud, $localidad]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO establecimientos (nombre, domicilio, localidad, latitud, longitud, direccion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $domicilio, $localidad, $latitud, $longitud, $direccion]);
        }
        $establecimiento_id = $pdo->lastInsertId();
    }

    // Asociar a la habilitación
    $tipoDestino = $esRemis ? 'remiseria' : 'establecimiento';
    $stmt = $pdo->prepare("INSERT INTO habilitaciones_establecimientos (habilitacion_id, establecimiento_id, tipo) VALUES (?, ?, ?)");
    $stmt->execute([$habilitacion_id, $establecimiento_id, $tipoDestino]);

    $_SESSION['mensaje_exito'] = "✅ " . ucfirst($tipoDestino) . " asociado correctamente.";
    header("Location: asociar_establecimiento.php?id=$habilitacion_id");
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = "❌ Error al procesar: " . $e->getMessage();
    header("Location: asociar_establecimiento.php?id=$habilitacion_id");
    exit;
}
?>
