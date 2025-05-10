<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nro_licencia = $_POST['nro_licencia'] ?? '';
    $anio = $_POST['anio'] ?? '';
    $vigencia_inicio = $_POST['vigencia_inicio'] ?? '';
    $vigencia_fin = $_POST['vigencia_fin'] ?? '';
    $tipo_transporte = $_POST['tipo_transporte'] ?? '';
    $estado = $_POST['estado'] ?? '';

    if ($id && is_numeric($id)) {
        // Actualizar datos generales
        $stmt = $pdo->prepare("UPDATE habilitaciones_generales 
            SET nro_licencia = ?, anio = ?, vigencia_inicio = ?, vigencia_fin = ?, tipo_transporte = ?, estado = ? 
            WHERE id = ?");
        $stmt->execute([
            $nro_licencia, $anio, $vigencia_inicio, $vigencia_fin, $tipo_transporte, $estado, $id
        ]);

        // Actualizar personas
        if (!empty($_POST['personas']) && is_array($_POST['personas'])) {
            foreach ($_POST['personas'] as $persona_id => $datos) {
                $rol = $datos['rol'] ?? '';
                $licencia_categoria = $datos['licencia_categoria'] ?? '';
                $stmt = $pdo->prepare("UPDATE habilitaciones_personas 
                    SET rol = ?, licencia_categoria = ? 
                    WHERE id = ?");
                $stmt->execute([$rol, $licencia_categoria, $persona_id]);
            }
        }

        // Actualizar vehículo
        $dominio = $_POST['vehiculo_dominio'] ?? '';
        $marca = $_POST['vehiculo_marca'] ?? '';
        $modelo = $_POST['vehiculo_modelo'] ?? '';

        $stmt = $pdo->prepare("SELECT vehiculo_id FROM habilitaciones_vehiculos WHERE habilitacion_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $vehiculo_id = $stmt->fetchColumn();

        if ($vehiculo_id) {
            $stmt = $pdo->prepare("UPDATE vehiculos 
                SET dominio = ?, marca = ?, modelo = ? 
                WHERE id = ?");
            $stmt->execute([$dominio, $marca, $modelo, $vehiculo_id]);
        }

        // Actualizar establecimiento
        $nombre_est = $_POST['establecimiento_nombre'] ?? '';
        $domicilio_est = $_POST['establecimiento_domicilio'] ?? '';

        $stmt = $pdo->prepare("SELECT establecimiento_id FROM habilitaciones_establecimientos WHERE habilitacion_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $establecimiento_id = $stmt->fetchColumn();

        if ($establecimiento_id) {
            $stmt = $pdo->prepare("UPDATE establecimientos 
                SET nombre = ?, domicilio = ? 
                WHERE id = ?");
            $stmt->execute([$nombre_est, $domicilio_est, $establecimiento_id]);
        }

        // Redirigir al panel principal
        header("Location: index.php?edicion=ok");
        exit;
    }
}

// Si falla algo
header("Location: index.php?error=1");
exit;
?>
