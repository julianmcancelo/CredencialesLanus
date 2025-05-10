<?php
require_once 'conexion.php';

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dominio = $_POST['dominio'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $motor = $_POST['motor'];
    $asientos = $_POST['asientos'];
    $inscripcion_inicial = $_POST['inscripcion_inicial'];
    $habilitacion_id = $_POST['habilitacion_id'];

    $stmt = $pdo->prepare("INSERT INTO vehiculos (dominio, marca, modelo, motor, asientos, inscripcion_inicial) 
                           VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$dominio, $marca, $modelo, $motor, $asientos, $inscripcion_inicial])) {
        $vehiculo_id = $pdo->lastInsertId();

        $stmt_habilitacion = $pdo->prepare("INSERT INTO habilitaciones_vehiculos (habilitacion_id, vehiculo_id) 
                                           VALUES (?, ?)");
        $stmt_habilitacion->execute([$habilitacion_id, $vehiculo_id]);

        $mensaje = "✅ Vehículo registrado y asociado a la habilitación con éxito.";
    } else {
        $mensaje = "❌ Error al registrar el vehículo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Vehículo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-2xl bg-white p-6 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold text-[#00adee] mb-4 text-center">Registrar Vehículo</h1>

        <?php if ($mensaje): ?>
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm text-center"><?= $mensaje ?></div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="habilitacion_id" value="<?= $_GET['habilitacion_id'] ?>">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Dominio</label>
                <input name="dominio" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Marca</label>
                <input name="marca" class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo</label>
                <input name="modelo" class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Motor</label>
                <input name="motor" class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Asientos</label>
                <input name="asientos" type="number" class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Inscripción Inicial</label>
                <input name="inscripcion_inicial" type="date" class="w-full border px-3 py-2 rounded">
            </div>

            <div class="md:col-span-2 flex justify-end gap-2 mt-4">
                <a href="index.php" class="px-4 py-2 bg-gray-200 rounded text-sm">← Volver al Panel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">Registrar Vehículo</button>
            </div>
        </form>
    </div>
</body>
</html>
