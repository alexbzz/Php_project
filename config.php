<?php
$host = '127.0.0.1';
$port = 3306;
$dbname = 'projet_php';
$user = 'root';
$password = 'Maliklegay';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>