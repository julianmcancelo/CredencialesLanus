<?php
require_once 'conexion.php';

try {
    // Verificar si la licencia ya existe
    $check = $pdo->prepare("SELECT id FROM habilitaciones_generales WHERE nro_licencia = ?");
    $check->execute([$_POST['nro_licencia']]);

    if ($check->rowCount() > 0) {
        echo "<script>alert('La licencia ya está registrada.'); history.back();</script>";
        exit;
    }

    // Insertar nueva habilitación general
    $stmt = $pdo->prepare("INSERT INTO habilitaciones_generales
        (nro_licencia, tipo_habilitacion, resolucion, expediente, vigencia_inicio, vigencia_fin, estado, observacion, contacto, email)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $_POST['nro_licencia'], $_POST['tipo_habilitacion'], $_POST['resolucion'], $_POST['expediente'],
        $_POST['vigencia_inicio'], $_POST['vigencia_fin'], $_POST['estado'],
        $_POST['observacion'], $_POST['contacto'], $_POST['email']
    ]);

    $id_generado = $pdo->lastInsertId();

    echo "<script>alert('Datos generales guardados correctamente.'); window.location.href='formulario_personas.php?id=" . $id_generado . "';</script>";

} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>