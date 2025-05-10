<?php
require_once 'conexion.php';

$usuario_id = $_GET['id'] ?? null;

if (!$usuario_id || !is_numeric($usuario_id)) {
    die("ID inválido.");
}

$token = bin2hex(random_bytes(32)); // Genera un token seguro

$stmt = $pdo->prepare("UPDATE usuarios SET token_autologin = ? WHERE id = ?");
$stmt->execute([$token, $usuario_id]);

echo "✅ Token generado:<br>";
echo "<code>$token</code><br><br>";
echo "🔗 <a href='index.php?token=$token' target='_blank'>Acceder directamente al sistema</a>";
