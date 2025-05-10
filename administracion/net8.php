<?php
// conexion.php
$pdo = new PDO('mysql:host=localhost;dbname=transpo1_credenciales;charset=utf8mb4', 'transpo1_credenciales', 'feelthesky1', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Manejo de acciones
if (isset($_GET['accion'])) {
    switch ($_GET['accion']) {
        case 'agregar':
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, legajo, hwid, estado, nombre_completo, rol) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['usuario'], $_POST['legajo'], $_POST['hwid'], $_POST['estado'], $_POST['nombre_completo'], $_POST['rol']]);
            break;

        case 'editar':
            $stmt = $pdo->prepare("UPDATE usuarios SET usuario=?, legajo=?, hwid=?, estado=?, nombre_completo=?, rol=? WHERE id=?");
            $stmt->execute([$_POST['usuario'], $_POST['legajo'], $_POST['hwid'], $_POST['estado'], $_POST['nombre_completo'], $_POST['rol'], $_POST['id']]);
            break;

        case 'eliminar':
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");
            $stmt->execute([$_GET['id']]);
            break;
    }

    header("Location: {$_SERVER['PHP_SELF']}");
    exit;
}

// Obtener usuarios
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY creado_en DESC");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h1 class="mb-4">Gestión de Usuarios</h1>

    <!-- Formulario para agregar/editar usuarios -->
    <form method="post" action="?accion=agregar" class="mb-4">
        <input type="hidden" name="id" id="userId">
        <div class="row g-3">
            <div class="col-md-2">
                <input class="form-control" type="text" name="usuario" placeholder="Usuario" required id="usuario">
            </div>
            <div class="col-md-2">
                <input class="form-control" type="text" name="legajo" placeholder="Legajo" required id="legajo">
            </div>
            <div class="col-md-3">
                <input class="form-control" type="text" name="hwid" placeholder="HWID" required id="hwid">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado" id="estado">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="col-md-3">
                <input class="form-control" type="text" name="nombre_completo" placeholder="Nombre Completo" required id="nombre_completo">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="rol" id="rol">
                    <option value="usuario">Usuario</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" type="submit">Guardar</button>
            </div>
        </div>
    </form>

    <!-- Tabla de usuarios -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Legajo</th>
                <th>HWID</th>
                <th>Estado</th>
                <th>Nombre Completo</th>
                <th>Rol</th>
                <th>Fecha Creación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= htmlspecialchars($usuario['id']) ?></td>
                    <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                    <td><?= htmlspecialchars($usuario['legajo']) ?></td>
                    <td><?= htmlspecialchars($usuario['hwid']) ?></td>
                    <td><?= htmlspecialchars($usuario['estado']) ?></td>
                    <td><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                    <td><?= htmlspecialchars($usuario['rol']) ?></td>
                    <td><?= htmlspecialchars($usuario['creado_en']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editarUsuario(<?= htmlspecialchars(json_encode($usuario)) ?>)">Editar</button>
                        <a href="?accion=eliminar&id=<?= htmlspecialchars($usuario['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function editarUsuario(user) {
            document.querySelector('form').action = '?accion=editar';
            document.getElementById('userId').value = user.id;
            document.getElementById('usuario').value = user.usuario;
            document.getElementById('legajo').value = user.legajo;
            document.getElementById('hwid').value = user.hwid;
            document.getElementById('estado').value = user.estado;
            document.getElementById('nombre_completo').value = user.nombre_completo;
            document.getElementById('rol').value = user.rol;
        }
    </script>
</body>
</html>