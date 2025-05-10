<?php
require_once 'conexion.php';

try {
    $id = intval($_POST['habilitacion_id']);

    // Titular
    if (!empty($_POST['titular_nombre']) && !empty($_POST['titular_dni'])) {
        $stmt = $pdo->prepare("INSERT INTO titulares (habilitacion_id, nombre, dni, cuit, foto_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $_POST['titular_nombre'],
            $_POST['titular_dni'],
            $_POST['titular_cuit'],
            $_POST['titular_foto_url']
        ]);
    }

    // Conductor
    if (!empty($_POST['conductor_nombre']) && !empty($_POST['conductor_dni'])) {
        $stmt = $pdo->prepare("INSERT INTO conductores (habilitacion_id, nombre, dni, licencia, foto_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $_POST['conductor_nombre'],
            $_POST['conductor_dni'],
            $_POST['conductor_licencia'],
            $_POST['conductor_foto_url']
        ]);
    }

    // Celador
    if (!empty($_POST['celador_nombre']) && !empty($_POST['celador_dni'])) {
        $stmt = $pdo->prepare("INSERT INTO celadores (habilitacion_id, nombre, dni) VALUES (?, ?, ?)");
        $stmt->execute([
            $id,
            $_POST['celador_nombre'],
            $_POST['celador_dni']
        ]);
    }

    echo "<script>alert('Datos guardados correctamente.'); window.location.href='formulario_establecimiento_vehiculo.php?id=$id';</script>";

} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
