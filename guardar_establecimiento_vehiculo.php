<?php
require_once 'conexion.php';

try {
    $id = intval($_POST['habilitacion_id']);

    // Insertar establecimiento si existe
    if (!empty($_POST['establecimiento_nombre'])) {
        $stmt = $pdo->prepare("INSERT INTO establecimientos
            (habilitacion_id, nombre, domicilio, localidad, latitud, longitud)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $_POST['establecimiento_nombre'],
            $_POST['establecimiento_domicilio'],
            $_POST['establecimiento_localidad'],
            $_POST['establecimiento_latitud'],
            $_POST['establecimiento_longitud']
        ]);
    }

    // Insertar datos del vehículo
    $stmt = $pdo->prepare("INSERT INTO vehiculos
        (habilitacion_id, marca, modelo, motor, asientos, anio, inscripcion_inicial)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $id,
        $_POST['marca'],
        $_POST['modelo'],
        $_POST['motor'],
        $_POST['asientos'],
        $_POST['anio'],
        $_POST['inscripcion_inicial']
    ]);

    echo "<script>alert('Registro completado correctamente.'); window.location.href='credencial.php?id=$id';</script>";

} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>