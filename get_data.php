<?php
header('Content-Type: application/json; charset=utf-8');

$host   = 'localhost';
$dbname = 'meteo_db';
$user   = 'root';
$pass   = '';

$hours = isset($_GET['hours']) ? intval($_GET['hours']) : 24;
if (!in_array($hours, [1, 6, 12, 24])) $hours = 24;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT temperature, humidity, timestamp FROM data WHERE timestamp >= NOW() - INTERVAL :h HOUR ORDER BY timestamp ASC");
    $stmt->bindParam(':h', $hours, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
