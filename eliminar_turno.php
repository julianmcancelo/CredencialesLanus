<?php
require 'conexion.php';
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM turnos WHERE id = ?");
$stmt->execute([$id]);
