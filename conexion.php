<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'transpo1_credenciales');
define('DB_USER', 'transpo1_credenciales');
define('DB_PASS', 'feelthesky1');

try {
  $pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (PDOException $e) {
  die('Error de conexión: ' . $e->getMessage());
}
?>
