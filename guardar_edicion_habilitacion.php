<?php
require_once 'conexion.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar que solo admin pueda guardar
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        header("Location: index.php?error=1");
        exit;
    }

    $nro_licencia = trim($_POST['nro_licencia'] ?? '');
    $resolucion = trim($_POST['resolucion'] ?? '');
    $vigencia_inicio = $_POST['vigencia_inicio'] ?? null;
    $vigencia_fin = $_POST['vigencia_fin'] ?? null;
    $estado = $_POST['estado'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $observaciones = $_POST['observaciones'] ?? null;
    $expte = trim($_POST['expte'] ?? '');
    $tipo_transporte = $_POST['tipo_transporte'] ?? '';

    try {
        $pdo->beginTransaction();

        // Actualizar habilitación
        $stmt = $pdo->prepare("
            UPDATE habilitaciones_generales 
            SET nro_licencia = ?, resolucion = ?, vigencia_inicio = ?, vigencia_fin = ?, 
                estado = ?, tipo = ?, observaciones = ?, expte = ?, tipo_transporte = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $nro_licencia,
            $resolucion,
            $vigencia_inicio,
            $vigencia_fin,
            $estado,
            $tipo,
            $observaciones,
            $expte,
            $tipo_transporte,
            $id
        ]);

        // También actualizar la fecha de expiración en tokens_acceso
        $stmt = $pdo->prepare("
            UPDATE tokens_acceso 
            SET fecha_expiracion = ? 
            WHERE habilitacion_id = ?
        ");
        $stmt->execute([
            $vigencia_fin,
            $id
        ]);

        $pdo->commit();

        // Redirigir al panel con mensaje de éxito
        header("Location: index.php?edicion=ok");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("❌ Error al actualizar la habilitación: " . $e->getMessage());
    }
} else {
    // No es POST
    header("Location: index.php");
    exit;
}
?>
