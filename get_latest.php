<?php
header('Content-Type: application/json; charset=utf-8');

$host   = 'localhost';
$dbname = 'meteo_db';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT temperature, humidity, timestamp FROM data ORDER BY timestamp DESC LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($data ?: null);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
